<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Acl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->orderByDesc('is_admin')->orderBy('nama')->get();
        $userCounts = User::selectRaw('role, COUNT(*) as c')->groupBy('role')->pluck('c', 'role');

        return view('roles.index', compact('roles', 'userCounts'));
    }

    public function create()
    {
        return view('roles.form', ['role' => new Role(['is_admin' => false]), 'dipilih' => [], 'grup' => Acl::groups()]);
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

        DB::transaction(function () use ($request, $data) {
            $role = Role::create([
                'key' => Str::slug($data['key'], '_'),
                'nama' => $data['nama'],
                'is_admin' => $request->boolean('is_admin'),
            ]);
            $this->syncPermissions($role, $request);
        });

        return redirect()->route('roles.index')->with('success', 'Role dibuat.');
    }

    public function edit(Role $role)
    {
        return view('roles.form', [
            'role' => $role,
            'dipilih' => $role->permissionKeys(),
            'grup' => Acl::groups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'is_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', Acl::all()),
        ]);

        // jangan sampai role admin terakhir kehilangan is_admin
        if ($role->is_admin && ! $request->boolean('is_admin') && Role::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu role admin (akses penuh).');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update(['nama' => $request->nama, 'is_admin' => $request->boolean('is_admin')]);
            $this->syncPermissions($role, $request);
        });

        return redirect()->route('roles.index')->with('success', 'Role & akses diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (User::where('role', $role->key)->exists()) {
            return back()->with('error', 'Role masih dipakai pengguna. Pindahkan dulu penggunanya.');
        }
        if ($role->is_admin && Role::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus role admin terakhir.');
        }
        $role->delete();

        return back()->with('success', 'Role dihapus.');
    }

    private function syncPermissions(Role $role, Request $request): void
    {
        $role->permissions()->delete();
        if (! $request->boolean('is_admin')) { // admin = full access, tak perlu daftar izin
            foreach ($request->input('permissions', []) as $perm) {
                $role->permissions()->create(['permission' => $perm]);
            }
        }
    }
}
