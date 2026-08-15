<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Part;
use App\Models\Platform;
use App\Models\Salary;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true,
        ]));
    }

    public function test_bayar_gaji_membuat_slip_dan_pengeluaran(): void
    {
        $emp = Employee::create(['nama' => 'Joko', 'jabatan' => 'Mekanik', 'gaji_pokok' => 3000000, 'aktif' => true]);

        $this->post(route('employees.salary.store', $emp), [
            'periode' => '2026-08', 'gaji_pokok' => 3000000, 'bonus' => 500000,
            'komisi' => 200000, 'potongan' => 100000, 'tgl_bayar' => '2026-08-28',
        ])->assertRedirect();

        $salary = Salary::first();
        $this->assertSame(3600000.0, $salary->total_dibayar); // 3jt+500rb+200rb-100rb

        // pengeluaran otomatis kategori Gaji
        $expense = Expense::where('ref_tipe', 'salary')->first();
        $this->assertNotNull($expense);
        $this->assertSame(3600000.0, $expense->nominal);
        $this->assertSame($salary->id, $expense->ref_id);
        $this->assertSame('Gaji', $expense->category->nama);
    }

    public function test_gaji_periode_sama_tidak_bisa_dobel(): void
    {
        $emp = Employee::create(['nama' => 'Joko', 'gaji_pokok' => 3000000, 'aktif' => true]);
        $payload = ['periode' => '2026-08', 'gaji_pokok' => 3000000, 'tgl_bayar' => '2026-08-28'];

        $this->post(route('employees.salary.store', $emp), $payload);
        $this->post(route('employees.salary.store', $emp), $payload)->assertSessionHas('error');

        $this->assertSame(1, Salary::count());
    }

    public function test_batal_gaji_menghapus_pengeluaran(): void
    {
        $emp = Employee::create(['nama' => 'Joko', 'gaji_pokok' => 3000000, 'aktif' => true]);
        $this->post(route('employees.salary.store', $emp), ['periode' => '2026-08', 'gaji_pokok' => 3000000, 'tgl_bayar' => '2026-08-28']);

        $salary = Salary::first();
        $this->delete(route('employees.salary.destroy', [$emp, $salary]));

        $this->assertSame(0, Salary::count());
        $this->assertSame(0, Expense::count());
    }

    public function test_laba_rugi_menghitung_hpp_dan_pengeluaran(): void
    {
        $platform = Platform::create(['nama' => 'Kasir']);
        $part = Part::create(['kode' => 'P1', 'nama' => 'Oli', 'satuan' => 'pcs', 'harga_beli' => 40000, 'harga_jual' => 55000, 'stok' => 10, 'stok_min' => 1]);

        // jual 2 oli @55rb → omzet 110rb, HPP 80rb
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $platform->id, 'metode' => 'tunai', 'bayar' => 110000,
            'items' => [['tipe' => 'part', 'ref_id' => $part->id, 'nama' => 'Oli', 'qty' => 2, 'harga' => 55000, 'diskon' => 0]],
        ]);

        // pengeluaran 30rb
        Expense::create(['tanggal' => now()->toDateString(), 'nominal' => 30000, 'metode' => 'tunai', 'keterangan' => 'listrik']);

        $res = $this->get(route('reports.laba-rugi'));
        $res->assertOk();
        $res->assertViewHas('pendapatan', 110000.0);
        $res->assertViewHas('hpp', 80000.0);
        $res->assertViewHas('labaKotor', 30000.0);        // 110 - 80
        $res->assertViewHas('totalPengeluaran', 30000.0);
        $res->assertViewHas('labaBersih', 0.0);            // 30 - 30
    }

    public function test_arus_kas_masuk_dari_pembayaran(): void
    {
        $platform = Platform::create(['nama' => 'Kasir']);
        $part = Part::create(['kode' => 'P1', 'nama' => 'Oli', 'satuan' => 'pcs', 'harga_beli' => 40000, 'harga_jual' => 55000, 'stok' => 10, 'stok_min' => 1]);
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $platform->id, 'metode' => 'tunai', 'bayar' => 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0]],
        ]);
        Expense::create(['tanggal' => now()->toDateString(), 'nominal' => 20000, 'metode' => 'tunai', 'keterangan' => 'x']);

        $res = $this->get(route('reports.arus-kas'));
        $res->assertViewHas('kasMasuk', 55000.0);
        $res->assertViewHas('kasKeluar', 20000.0); // pengeluaran; pembelian 0
    }
}
