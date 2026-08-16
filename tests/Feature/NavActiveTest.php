<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavActiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]);
    }

    /** Ambil atribut class dari <a ...href berakhir $suffix ...> di HTML. */
    private function navClass(string $html, string $suffix): string
    {
        preg_match('/<a[^>]*href="[^"]*' . preg_quote($suffix, '/') . '"[^>]*class="([^"]*)"/', $html, $m);

        return $m[1] ?? '';
    }

    public function test_stok_aktif_tidak_menyalakan_transfer(): void
    {
        $html = $this->actingAs($this->admin())->get(route('stock.index'))->getContent();

        $this->assertStringContainsString('active', $this->navClass($html, '/stock'));       // Stok & Opname aktif
        $this->assertStringNotContainsString('active', $this->navClass($html, '/stock/transfer')); // Transfer TIDAK
    }

    public function test_transfer_aktif_tidak_menyalakan_stok(): void
    {
        $html = $this->actingAs($this->admin())->get(route('stock.transfer'))->getContent();

        $this->assertStringContainsString('active', $this->navClass($html, '/stock/transfer')); // Transfer aktif
        $this->assertStringNotContainsString('active', $this->navClass($html, '/stock'));        // Stok & Opname TIDAK
    }

    public function test_opname_menyalakan_menu_stok_saja(): void
    {
        $html = $this->actingAs($this->admin())->get(route('stock.opname'))->getContent();

        $this->assertStringContainsString('active', $this->navClass($html, '/stock'));           // Stok & Opname aktif
        $this->assertStringNotContainsString('active', $this->navClass($html, '/stock/transfer')); // Transfer TIDAK
    }
}
