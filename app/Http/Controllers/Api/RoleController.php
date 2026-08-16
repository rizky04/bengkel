<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Acl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index()
    {
        return Role::withCount('permissions')->orderByDesc('is_admin')->orderBy('nama')->get()
            ->map(fn ($r) => [...$r->toArray(), 'permissions' => $r->permissionKeys()]);
    }

    /** Katalog izin (grup → key → label) untuk membangun matriks centang di mobile. */
    public function acl()
    {
        return Acl::groups();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'key' => 'required|alpha_dash|max:50|unique:roles,key',
            'is_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', Acl::all()),
        ]);

        $role = DB::transaction(function () use ($request, $data) {
            $role = Role::create(['key' => Str::slug($data['key'], '_'), 'nama' => $data['nama'], 'is_admin' => $request->boolean('is_admin')]);
            $this->sync($role, $request);

            return $role;
        });

        return [...$role->toArray(), 'permissions' => $role->fresh()->permissionKeys()];
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'is_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', Acl::all()),
        ]);
        if ($role->is_admin && ! $request->boolean('is_admin') && Role::where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages(['is_admin' => 'Minimal harus ada satu role admin.']);
        }
        DB::transaction(function () use ($request, $role) {
            $role->update(['nama' => $request->nama, 'is_admin' => $request->boolean('is_admin')]);
            $this->sync($role, $request);
        });

        return [...$role->fresh()->toArray(), 'permissions' => $role->fresh()->permissionKeys()];
    }

    public function destroy(Role $role)
    {
        if (User::where('role', $role->key)->exists()) {
            throw ValidationException::withMessages(['id' => 'Role masih dipakai pengguna.']);
        }
        if ($role->is_admin && Role::where('is_admin', true)->count() <= 1) {
            throw ValidationException::withMessages(['id' => 'Tidak bisa menghapus role admin terakhir.']);
        }
        $role->delete();

        return ['ok' => true];
    }

    private function sync(Role $role, Request $request): void
    {
        $role->permissions()->delete();
        if (! $request->boolean('is_admin')) {
            foreach ($request->input('permissions', []) as $perm) {
                $role->permissions()->create(['permission' => $perm]);
            }
        }
    }
}
