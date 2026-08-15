<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true,
        ]);
        $this->actingAs($user);

        $this->part = Part::create([
            'kode' => 'SP1', 'nama' => 'Oli', 'satuan' => 'pcs',
            'harga_beli' => 40000, 'harga_jual' => 50000, 'stok' => 0, 'stok_min' => 2,
        ]);
    }

    public function test_stok_masuk_tercatat_di_kartu_stok(): void
    {
        $this->part->moveStock('in', 10, ['tipe' => 'test']);

        $this->assertSame(10, $this->part->fresh()->stok);

        $move = $this->part->stockMoves()->latest('id')->first();
        $this->assertSame(10, $move->qty);
        $this->assertSame(10, $move->saldo);
        $this->assertSame('in', $move->tipe);
    }

    public function test_stok_keluar_mengurangi_stok(): void
    {
        $this->part->moveStock('in', 10);
        $this->part->moveStock('out', 3);

        $this->assertSame(7, $this->part->fresh()->stok);
        $this->assertSame(-3, $this->part->stockMoves()->latest('id')->first()->qty);
    }

    public function test_pengeluaran_melebihi_stok_ditolak_tanpa_mengubah_data(): void
    {
        $this->part->moveStock('in', 5);

        $this->expectException(\RuntimeException::class);

        try {
            $this->part->moveStock('out', 8);
        } finally {
            $this->assertSame(5, $this->part->fresh()->stok);
            // mutasi yang gagal tidak boleh tercatat
            $this->assertSame(1, $this->part->stockMoves()->count());
        }
    }

    public function test_opname_mencatat_selisih_sebagai_penyesuaian(): void
    {
        $this->part->moveStock('in', 10);

        $this->post(route('stock.opname.store'), [
            'fisik' => [$this->part->id => 7],
            'keterangan' => 'Opname uji',
        ])->assertRedirect(route('stock.index'));

        $this->assertSame(7, $this->part->fresh()->stok);

        $move = $this->part->stockMoves()->latest('id')->first();
        $this->assertSame('adjust', $move->tipe);
        $this->assertSame(-3, $move->qty);
        $this->assertSame(7, $move->saldo);
    }

    public function test_opname_melewati_baris_kosong(): void
    {
        $this->part->moveStock('in', 10);

        $this->post(route('stock.opname.store'), ['fisik' => [$this->part->id => null]]);

        $this->assertSame(10, $this->part->fresh()->stok);
        $this->assertSame(1, $this->part->stockMoves()->count());
    }

    public function test_pembelian_menambah_stok_dan_memperbarui_harga_beli(): void
    {
        $supplier = Supplier::create(['nama' => 'PT Sparepart']);

        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'tgl' => now()->toDateString(),
            'items' => [['part_id' => $this->part->id, 'qty' => 6, 'harga_beli' => 42000]],
        ])->assertRedirect();

        $part = $this->part->fresh();
        $this->assertSame(6, $part->stok);
        $this->assertSame(42000.0, $part->harga_beli);
        $this->assertSame(252000.0, (float) Purchase::first()->total);
    }

    public function test_pembatalan_pembelian_mengembalikan_stok(): void
    {
        $this->post(route('purchases.store'), [
            'tgl' => now()->toDateString(),
            'items' => [['part_id' => $this->part->id, 'qty' => 5, 'harga_beli' => 40000]],
        ]);
        $this->assertSame(5, $this->part->fresh()->stok);

        $this->delete(route('purchases.destroy', Purchase::first()));

        $this->assertSame(0, $this->part->fresh()->stok);
        $this->assertSame(0, Purchase::count());
    }

    public function test_membuat_part_dengan_stok_awal_membuat_kartu_stok(): void
    {
        $this->post(route('parts.store'), [
            'kode' => 'SP2', 'nama' => 'Busi', 'satuan' => 'pcs',
            'harga_beli' => 15000, 'harga_jual' => 20000, 'stok' => 12, 'stok_min' => 3,
        ])->assertRedirect(route('parts.index'));

        $part = Part::where('kode', 'SP2')->first();
        $this->assertSame(12, $part->stok);
        $this->assertSame(12, $part->stockMoves()->sum('qty'));
    }
}
