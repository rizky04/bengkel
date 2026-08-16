<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Part;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_terpisah_per_cabang(): void
    {
        $a = Branch::create(['nama' => 'Cabang A', 'aktif' => true]);
        $b = Branch::create(['nama' => 'Cabang B', 'aktif' => true]);
        $admin = User::create(['name' => 'Adm', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $a->id]);
        $this->actingAs($admin);

        $part = Part::create(['kode' => 'P1', 'nama' => 'Oli', 'satuan' => 'pcs', 'harga_beli' => 40000, 'harga_jual' => 55000, 'stok_min' => 1]);
        $part->moveStock($a->id, 'in', 10);
        $part->moveStock($b->id, 'in', 3);

        $this->assertSame(10, $part->stokDi($a->id));
        $this->assertSame(3, $part->stokDi($b->id));
    }

    public function test_transfer_stok_antar_cabang(): void
    {
        $a = Branch::create(['nama' => 'Cabang A', 'aktif' => true]);
        $b = Branch::create(['nama' => 'Cabang B', 'aktif' => true]);
        $admin = User::create(['name' => 'Adm', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $a->id]);
        $this->actingAs($admin); // cabang aktif = A

        $part = Part::create(['kode' => 'P1', 'nama' => 'Oli', 'satuan' => 'pcs', 'harga_beli' => 40000, 'harga_jual' => 55000, 'stok_min' => 1]);
        $part->moveStock($a->id, 'in', 10);

        $this->post(route('stock.transfer.store'), ['part_id' => $part->id, 'ke_branch_id' => $b->id, 'qty' => 4])
            ->assertRedirect();

        $this->assertSame(6, $part->stokDi($a->id)); // 10 - 4
        $this->assertSame(4, $part->stokDi($b->id));
    }

    public function test_penjualan_memotong_stok_cabang_aktif_saja(): void
    {
        $a = Branch::create(['nama' => 'Cabang A', 'aktif' => true]);
        $b = Branch::create(['nama' => 'Cabang B', 'aktif' => true]);
        $platform = Platform::create(['nama' => 'Kasir']);
        $admin = User::create(['name' => 'Adm', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $a->id]);
        $this->actingAs($admin);

        $part = Part::create(['kode' => 'P1', 'nama' => 'Oli', 'satuan' => 'pcs', 'harga_beli' => 40000, 'harga_jual' => 55000, 'stok_min' => 1]);
        $part->moveStock($a->id, 'in', 10);
        $part->moveStock($b->id, 'in', 10);

        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $platform->id, 'metode' => 'tunai', 'bayar' => 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0]],
        ]);

        $this->assertSame(8, $part->stokDi($a->id));  // cabang aktif berkurang
        $this->assertSame(10, $part->stokDi($b->id)); // cabang lain tetap
    }
}
