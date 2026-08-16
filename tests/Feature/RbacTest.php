<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $roleKey): User
    {
        return User::create([
            'name' => 'U', 'email' => $roleKey . '@b.test', 'password' => bcrypt('x'),
            'role' => $roleKey, 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]);
    }

    public function test_admin_role_akses_penuh(): void
    {
        $u = $this->user('admin'); // di-seed migrasi sebagai is_admin
        $this->assertTrue($u->isAdmin());
        $this->assertTrue($u->canAccess('users'));
        $this->assertTrue($u->canAccess('settings'));
    }

    public function test_izin_membatasi_menu(): void
    {
        // buat role custom: hanya boleh 'pos' & 'transactions'
        $role = Role::create(['key' => 'kasir2', 'nama' => 'Kasir 2', 'is_admin' => false]);
        $role->permissions()->createMany([['permission' => 'pos'], ['permission' => 'transactions']]);

        $u = $this->user('kasir2');
        $this->assertTrue($u->canAccess('pos'));
        $this->assertFalse($u->canAccess('users'));

        // route yang diizinkan → boleh; yang tidak → 403
        $this->actingAs($u)->get(route('pos.create'))->assertOk();
        $this->actingAs($u)->get(route('users.index'))->assertForbidden();
        $this->actingAs($u)->get(route('settings.edit'))->assertForbidden();
    }

    public function test_admin_membuat_role_dengan_izin(): void
    {
        $this->actingAs($this->user('admin'));

        $this->post(route('roles.store'), [
            'nama' => 'Supervisor', 'key' => 'supervisor', 'is_admin' => 0,
            'permissions' => ['dashboard', 'reports', 'stock'],
        ])->assertRedirect(route('roles.index'));

        $role = Role::where('key', 'supervisor')->first();
        $this->assertNotNull($role);
        $this->assertEqualsCanonicalizing(['dashboard', 'reports', 'stock'], $role->permissionKeys());
        $this->assertFalse($role->is_admin);
    }

    public function test_role_admin_dengan_flag_tidak_menyimpan_izin_eksplisit(): void
    {
        $this->actingAs($this->user('admin'));

        $this->post(route('roles.store'), ['nama' => 'Owner', 'key' => 'owner', 'is_admin' => 1, 'permissions' => ['pos']]);

        $role = Role::where('key', 'owner')->first();
        $this->assertTrue($role->is_admin);
        $this->assertCount(0, $role->permissionKeys()); // is_admin = bypass, tak simpan daftar
    }

    public function test_tak_bisa_hapus_role_yang_dipakai(): void
    {
        $this->actingAs($this->user('admin'));
        $kasir = Role::where('key', 'kasir')->first();
        $this->user('kasir'); // ada pengguna pakai role kasir

        $this->delete(route('roles.destroy', $kasir))->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $kasir->id]);
    }
}
