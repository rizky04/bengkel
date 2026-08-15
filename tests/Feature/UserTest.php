<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true]);
        $this->actingAs($this->admin);
    }

    public function test_admin_membuat_user_mekanik(): void
    {
        $this->post(route('users.store'), [
            'name' => 'Joko', 'email' => 'joko@b.test', 'password' => 'rahasia', 'role' => 'mekanik', 'aktif' => 1,
        ])->assertRedirect(route('users.index'));

        $u = User::where('email', 'joko@b.test')->first();
        $this->assertSame('mekanik', $u->role);
        $this->assertTrue(Hash::check('rahasia', $u->password));
    }

    public function test_update_tanpa_password_tidak_mengubah_password(): void
    {
        $u = User::create(['name' => 'X', 'email' => 'x@b.test', 'password' => Hash::make('lama123'), 'role' => 'kasir', 'aktif' => true]);

        $this->put(route('users.update', $u), ['name' => 'X2', 'email' => 'x@b.test', 'role' => 'kasir', 'password' => '']);

        $u->refresh();
        $this->assertSame('X2', $u->name);
        $this->assertTrue(Hash::check('lama123', $u->password)); // password lama tetap
    }

    public function test_tidak_bisa_hapus_akun_sendiri(): void
    {
        $this->delete(route('users.destroy', $this->admin))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_nonadmin_tak_boleh_akses(): void
    {
        $kasir = User::create(['name' => 'K', 'email' => 'k@b.test', 'password' => bcrypt('x'), 'role' => 'kasir', 'aktif' => true]);
        $this->actingAs($kasir)->get(route('users.index'))->assertForbidden();
    }
}
