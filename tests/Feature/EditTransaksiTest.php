<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\Platform;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditTransaksiTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Service $service;
    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]));
        $this->part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);
        $this->service = Service::create(['nama' => 'Ganti Oli', 'tarif' => 20000]);
        $this->platform = Platform::create(['nama' => 'Kasir']);
    }

    private function servisBerjalan(): Transaction
    {
        $c = \App\Models\Customer::create(['nama' => 'Budi']);
        $v = \App\Models\Vehicle::create(['customer_id' => $c->id, 'plat' => 'B1', 'jenis' => 'motor']);
        $this->post(route('pos.store'), [
            'tipe' => 'servis', 'platform_id' => $this->platform->id,
            'customer_id' => $c->id, 'vehicle_id' => $v->id, 'status_servis' => 'dikerjakan', 'bayar' => 0,
            'items' => [['tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 20000, 'diskon' => 0]],
        ]);

        return Transaction::latest('id')->first();
    }

    public function test_tambah_sparepart_saat_servis_potong_stok_dan_log(): void
    {
        $trx = $this->servisBerjalan();
        $this->assertSame(10, $this->part->fresh()->stok);

        $item = $trx->items->first(); // jasa

        $this->put(route('transactions.update', $trx), [
            'tgl' => $trx->tgl->format('Y-m-d\TH:i'), 'platform_id' => $this->platform->id,
            'items' => [
                ['id' => $item->id, 'tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 20000, 'diskon' => 0],
                ['id' => null, 'tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0],
            ],
        ])->assertRedirect();

        $this->assertSame(8, $this->part->fresh()->stok); // 10 - 2
        $trx->refresh();
        $this->assertSame(2, $trx->items()->count());
        $this->assertSame(130000.0, $trx->total); // 20rb + 110rb
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'edit_transaksi']);
        $this->assertStringContainsString('+Oli', ActivityLog::where('aksi', 'edit_transaksi')->latest('id')->value('deskripsi'));
    }

    public function test_kurangi_qty_item_mengembalikan_stok(): void
    {
        $trx = $this->servisBerjalan();
        // tambah 3 oli dulu
        $jasa = $trx->items->first();
        $this->put(route('transactions.update', $trx), [
            'tgl' => $trx->tgl->format('Y-m-d\TH:i'),
            'items' => [
                ['id' => $jasa->id, 'tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 20000, 'diskon' => 0],
                ['id' => null, 'tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 3, 'harga' => 55000, 'diskon' => 0],
            ],
        ]);
        $this->assertSame(7, $this->part->fresh()->stok);

        $oli = $trx->fresh()->items()->where('tipe', 'part')->first();
        // turunkan 3 → 1
        $this->put(route('transactions.update', $trx), [
            'tgl' => $trx->tgl->format('Y-m-d\TH:i'),
            'items' => [
                ['id' => $jasa->id, 'tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 20000, 'diskon' => 0],
                ['id' => $oli->id, 'tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0],
            ],
        ]);
        $this->assertSame(9, $this->part->fresh()->stok); // 7 + 2 kembali
    }

    public function test_edit_pembelian_ubah_qty_menyesuaikan_stok(): void
    {
        $sup = Supplier::create(['nama' => 'PT X']);
        $this->post(route('purchases.store'), [
            'supplier_id' => $sup->id, 'tgl' => now()->toDateString(), 'status' => 'lunas',
            'items' => [['part_id' => $this->part->id, 'qty' => 5, 'harga_beli' => 40000]],
        ]);
        $pur = Purchase::latest('id')->first();
        $this->assertSame(15, $this->part->fresh()->stok); // 10 + 5
        $item = $pur->items->first();

        // naikkan 5 → 8
        $this->put(route('purchases.update', $pur), [
            'supplier_id' => $sup->id, 'tgl' => now()->toDateString(), 'status' => 'lunas',
            'items' => [['id' => $item->id, 'part_id' => $this->part->id, 'qty' => 8, 'harga_beli' => 40000]],
        ])->assertRedirect();

        $this->assertSame(18, $this->part->fresh()->stok); // 15 + 3
        $this->assertSame(320000.0, (float) $pur->fresh()->total);
        $this->assertDatabaseHas('activity_logs', ['aksi' => 'edit_pembelian']);
    }
}
