<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['role' => 'kasir', 'aktif' => true]), 'branches' => \App\Models\Branch::orderBy('nama')->get(), 'roles' => \App\Models\Role::orderBy('nama')->get()]);
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

        $baru = User::create($data);
        \App\Models\ActivityLog::catat('user_baru', "{$baru->name} ({$baru->role})", 'user', $baru->id);

        return redirect()->route('users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user') + ['branches' => \App\Models\Branch::orderBy('nama')->get(), 'roles' => \App\Models\Role::orderBy('nama')->get()]);
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

        return redirect()->route('users.index')->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();

        return back()->with('success', 'Pengguna dihapus.');
    }
}
