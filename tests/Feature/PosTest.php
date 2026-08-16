<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Platform;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Service $service;
    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'name' => 'Kasir', 'email' => 'k@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]));

        $this->part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);
        $this->service = Service::create(['nama' => 'Ganti Oli', 'tarif' => 20000]);
        $this->platform = Platform::create(['nama' => 'Kasir']);
    }

    public function test_split_payment_dua_metode(): void
    {
        // total 110rb: bayar tunai 60rb + transfer 50rb
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id,
            'payments' => [
                ['metode' => 'tunai', 'jumlah' => 60000],
                ['metode' => 'transfer', 'jumlah' => 50000],
            ],
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0]],
        ])->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('lunas', $trx->status);
        $this->assertSame(2, $trx->payments()->count());
        $this->assertSame(110000.0, $trx->dibayar);
        $this->assertSame(60000.0, (float) $trx->payments()->where('metode', 'tunai')->value('jumlah'));
        $this->assertSame(50000.0, (float) $trx->payments()->where('metode', 'transfer')->value('jumlah'));
    }

    public function test_split_payment_overpay_tunai_dibatasi_ke_total(): void
    {
        // total 55rb: transfer 30rb + tunai 40rb (lebih 15rb = kembalian) → tercatat 55rb
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id,
            'payments' => [
                ['metode' => 'transfer', 'jumlah' => 30000],
                ['metode' => 'tunai', 'jumlah' => 40000],
            ],
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0]],
        ])->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('lunas', $trx->status);
        $this->assertSame(55000.0, $trx->dibayar); // tercatat = total, bukan 70rb
        $this->assertSame(30000.0, (float) $trx->payments()->where('metode', 'transfer')->value('jumlah'));
        $this->assertSame(25000.0, (float) $trx->payments()->where('metode', 'tunai')->value('jumlah')); // sisa 25rb, 15rb kembalian
    }

    public function test_penjualan_langsung_lunas_memotong_stok(): void
    {
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan',
            'platform_id' => $this->platform->id,
            'metode' => 'tunai',
            'bayar' => 110000,
            'items' => [
                ['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0],
            ],
        ])->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('lunas', $trx->status);
        $this->assertSame(110000.0, $trx->total);
        $this->assertSame(110000.0, $trx->dibayar);
        $this->assertSame(0.0, $trx->sisa);
        $this->assertSame(8, $this->part->fresh()->stok); // 10 - 2
    }

    public function test_servis_membuat_work_order_status_antri(): void
    {
        $customer = Customer::create(['nama' => 'Budi']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plat' => 'B1', 'jenis' => 'motor']);

        $this->post(route('pos.store'), [
            'tipe' => 'servis',
            'platform_id' => $this->platform->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status_servis' => 'antri',
            'metode' => 'tunai',
            'bayar' => 0,
            'items' => [
                ['tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 20000, 'diskon' => 0],
                ['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0],
            ],
        ])->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame('servis', $trx->tipe);
        $this->assertSame('antri', $trx->status);
        $this->assertSame(75000.0, $trx->total);
        $this->assertSame(75000.0, $trx->sisa); // belum bayar
        $this->assertSame(9, $this->part->fresh()->stok); // part servis tetap potong stok
    }

    public function test_servis_wajib_pelanggan_dan_kendaraan(): void
    {
        $this->post(route('pos.store'), [
            'tipe' => 'servis',
            'metode' => 'tunai',
            'items' => [['tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'x', 'qty' => 1, 'harga' => 20000]],
        ])->assertSessionHasErrors(['customer_id', 'vehicle_id']);

        $this->assertSame(0, Transaction::count());
    }

    public function test_promo_persen_mengurangi_total(): void
    {
        $promo = Promo::create(['nama' => 'Diskon 10%', 'jenis' => 'persen', 'nilai' => 10, 'aktif' => true]);

        $this->post(route('pos.store'), [
            'tipe' => 'penjualan',
            'platform_id' => $this->platform->id,
            'promo_id' => $promo->id,
            'metode' => 'tunai',
            'bayar' => 99000,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0]],
        ])->assertRedirect();

        $trx = Transaction::first();
        $this->assertSame(11000.0, $trx->diskon); // 10% dari 110000
        $this->assertSame(99000.0, $trx->total);
        $this->assertSame(1, $promo->fresh()->terpakai);
    }

    public function test_pembayaran_bertahap_melunasi_transaksi(): void
    {
        $customer = Customer::create(['nama' => 'Budi']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plat' => 'B1', 'jenis' => 'motor']);

        $this->post(route('pos.store'), [
            'tipe' => 'servis', 'platform_id' => $this->platform->id,
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id,
            'status_servis' => 'selesai', 'metode' => 'tunai', 'bayar' => 20000,
            'items' => [['tipe' => 'jasa', 'ref_id' => $this->service->id, 'nama' => 'Ganti Oli', 'qty' => 1, 'harga' => 50000, 'diskon' => 0]],
        ]);

        $trx = Transaction::first();
        $this->assertSame('selesai', $trx->status); // belum lunas
        $this->assertSame(30000.0, $trx->sisa);

        // pelunasan
        $this->post(route('transactions.payment', $trx), ['jumlah' => 30000, 'metode' => 'tunai']);

        $trx->refresh();
        $this->assertSame(0.0, $trx->sisa);
        $this->assertSame('lunas', $trx->status);
    }

    public function test_membatalkan_transaksi_mengembalikan_stok(): void
    {
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id,
            'metode' => 'tunai', 'bayar' => 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 3, 'harga' => 55000, 'diskon' => 0]],
        ]);
        $this->assertSame(7, $this->part->fresh()->stok);

        $trx = Transaction::first();
        $this->delete(route('transactions.cancel', $trx), ['alasan_batal' => 'uji batal']);

        $this->assertSame('batal', $trx->fresh()->status);
        $this->assertSame(10, $this->part->fresh()->stok);
    }

    public function test_menjual_melebihi_stok_ditolak(): void
    {
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id,
            'metode' => 'tunai', 'bayar' => 0,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 99, 'harga' => 55000, 'diskon' => 0]],
        ])->assertSessionHas('error');

        $this->assertSame(0, Transaction::count()); // rollback
        $this->assertSame(10, $this->part->fresh()->stok);
    }
}
