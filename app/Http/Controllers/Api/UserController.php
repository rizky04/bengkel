<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return User::with('branch:id,nama')->orderBy('name')->get(['id', 'name', 'email', 'role', 'branch_id', 'aktif']);
    }

    public function roles()
    {
        return Role::orderBy('nama')->get(['id', 'key', 'nama', 'is_admin']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|exists:roles,key',
            'branch_id' => 'nullable|exists:branches,id',
            'aktif' => 'nullable|boolean',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['aktif'] = $request->boolean('aktif');
        $user = User::create($data);
        ActivityLog::catat('user_baru', "{$user->name} ({$user->role})", 'user', $user->id);

        return $user->only('id', 'name', 'email', 'role', 'branch_id', 'aktif');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'required|exists:roles,key',
            'branch_id' => 'nullable|exists:branches,id',
            'aktif' => 'nullable|boolean',
        ]);
        $data['aktif'] = $request->boolean('aktif');
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return $user->only('id', 'name', 'email', 'role', 'branch_id', 'aktif');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['id' => 'Tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();

        return ['ok' => true];
    }
}
