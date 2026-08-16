<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\Promo;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create([
            'name' => 'Kasir', 'email' => 'k@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]));
        $this->platform = Platform::create(['nama' => 'Kasir']);
    }

    private function jual(array $extra = [])
    {
        return $this->post(route('pos.store'), array_merge([
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id, 'metode' => 'tunai', 'bayar' => 100000,
            'items' => [['tipe' => 'jasa', 'ref_id' => 1, 'nama' => 'Servis', 'qty' => 1, 'harga' => 100000, 'diskon' => 0]],
        ], $extra));
    }

    public function test_voucher_valid_endpoint(): void
    {
        $promo = Promo::create(['nama' => 'Voucher 10rb', 'kode' => 'HEMAT10', 'jenis' => 'nominal', 'nilai' => 10000, 'aktif' => true]);

        $this->get(route('pos.voucher', ['kode' => 'hemat10', 'subtotal' => 100000]))
            ->assertOk()->assertJson(['ok' => true, 'promo' => ['id' => $promo->id]]);

        $this->get(route('pos.voucher', ['kode' => 'SALAH', 'subtotal' => 100000]))
            ->assertJson(['ok' => false]);
    }

    public function test_voucher_min_belanja(): void
    {
        Promo::create(['nama' => 'V', 'kode' => 'BIG', 'jenis' => 'persen', 'nilai' => 10, 'min_belanja' => 200000, 'aktif' => true]);

        $this->get(route('pos.voucher', ['kode' => 'BIG', 'subtotal' => 50000]))->assertJson(['ok' => false]);
        $this->get(route('pos.voucher', ['kode' => 'BIG', 'subtotal' => 300000]))->assertJson(['ok' => true]);
    }

    public function test_kuota_voucher_ditegakkan(): void
    {
        $promo = Promo::create(['nama' => 'V', 'kode' => 'X', 'jenis' => 'nominal', 'nilai' => 10000, 'kuota' => 1, 'terpakai' => 0, 'aktif' => true]);

        // pemakaian pertama: diskon berlaku, terpakai jadi 1
        $this->jual(['promo_id' => $promo->id, 'bayar' => 90000]);
        $t1 = Transaction::latest('id')->first();
        $this->assertSame(10000.0, $t1->diskon);
        $this->assertSame(1, $promo->fresh()->terpakai);

        // pemakaian kedua: kuota habis → tanpa diskon & promo_id null
        $this->jual(['promo_id' => $promo->id, 'bayar' => 100000]);
        $t2 = Transaction::latest('id')->first();
        $this->assertSame(0.0, $t2->diskon);
        $this->assertNull($t2->promo_id);
        $this->assertSame(1, $promo->fresh()->terpakai); // tidak bertambah
    }

    public function test_konsolidasi_menjumlah_semua_cabang(): void
    {
        $b2 = \App\Models\Branch::create(['nama' => 'Cabang B', 'aktif' => true]);
        // transaksi di cabang aktif (Pusat) 100rb
        $this->jual();
        // transaksi di cabang B 50rb (buat langsung)
        Transaction::create(['no_nota' => 'INV-B', 'branch_id' => $b2->id, 'tipe' => 'penjualan', 'status' => 'lunas', 'subtotal' => 50000, 'total' => 50000, 'tgl' => now()]);

        $res = $this->get(route('reports.konsolidasi'));
        $res->assertOk();
        $rows = $res->viewData('rows');
        $this->assertSame(150000.0, (float) $rows->sum('pendapatan')); // 100rb + 50rb
    }
}
