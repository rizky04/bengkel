<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Purchase $purchase;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);

        Role::firstOrCreate(['key' => 'admin'], ['nama' => 'Admin', 'is_admin' => true]);
        $this->admin = User::create([
            'name' => 'A', 'email' => 'a@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]);

        // Buat pembelian 5 pcs
        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'tgl' => now()->format('Y-m-d'), 'status' => 'lunas',
            'items' => [['part_id' => $this->part->id, 'qty' => 5, 'harga_beli' => 40000]],
        ]);
        $this->purchase = Purchase::latest('id')->first();
    }

    public function test_retur_pembelian_mengurangi_stok(): void
    {
        $this->assertSame(15, $this->part->fresh()->stok); // 10 + 5 dari pembelian

        $item = $this->purchase->items->first();

        $this->actingAs($this->admin)->post(route('purchase-returns.store', $this->purchase), [
            'alasan' => 'barang cacat',
            'items' => [['purchase_item_id' => $item->id, 'qty' => 2]],
        ])->assertRedirect();

        $this->assertSame(13, $this->part->fresh()->stok); // 15 - 2
        $this->assertSame(1, PurchaseReturn::count());
        $this->assertSame(80000.0, PurchaseReturn::first()->total); // 2 × 40000
    }

    public function test_tidak_bisa_retur_melebihi_sisa(): void
    {
        $item = $this->purchase->items->first();

        // Retur 5 (habis semua)
        $this->actingAs($this->admin)->post(route('purchase-returns.store', $this->purchase), [
            'alasan' => 'pertama', 'items' => [['purchase_item_id' => $item->id, 'qty' => 5]],
        ])->assertRedirect();

        // Retur lagi 1 → tidak ada item yang bisa diretur → create redirect ke 404
        $this->actingAs($this->admin)->get(route('purchase-returns.create', $this->purchase))->assertNotFound();
    }

    public function test_sisaretur_dihitung_dari_semua_retur_sebelumnya(): void
    {
        $item = $this->purchase->items->first();

        // Retur 3 pertama
        $this->actingAs($this->admin)->post(route('purchase-returns.store', $this->purchase), [
            'alasan' => 'cacat pertama', 'items' => [['purchase_item_id' => $item->id, 'qty' => 3]],
        ]);

        $this->assertSame(2, $item->fresh()->sisaRetur()); // 5 - 3 = 2

        // Retur 2 lagi
        $this->actingAs($this->admin)->post(route('purchase-returns.store', $this->purchase), [
            'alasan' => 'cacat kedua', 'items' => [['purchase_item_id' => $item->id, 'qty' => 2]],
        ]);

        $this->assertSame(0, $item->fresh()->sisaRetur());
        $this->assertSame(10, $this->part->fresh()->stok); // 15 - 5 = 10 (balik ke awal)
    }
}
