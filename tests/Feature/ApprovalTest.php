<?php

namespace Tests\Feature;

use App\Models\EditRequest;
use App\Models\Part;
use App\Models\Platform;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Alur approval: kasir ajukan → admin setujui/tolak. */
class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Platform $platform;
    private User $kasir;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);
        $this->platform = Platform::create(['nama' => 'Kasir']);

        $rk = Role::create(['key' => 'kasir_ap', 'nama' => 'Kasir', 'is_admin' => false]);
        foreach (['pos', 'transactions'] as $p) {
            $rk->permissions()->create(['permission' => $p]);
        }
        $this->kasir = User::create(['name' => 'K', 'email' => 'k@b.test', 'password' => bcrypt('x'), 'role' => 'kasir_ap', 'aktif' => true, 'branch_id' => $this->branch()->id]);

        Role::firstOrCreate(['key' => 'admin'], ['nama' => 'Admin', 'is_admin' => true]);
        $this->admin = User::create(['name' => 'A', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id]);
    }

    private function jualLunas(): Transaction
    {
        $this->actingAs($this->kasir)->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id, 'metode' => 'tunai', 'bayar' => 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0]],
        ]);

        return Transaction::latest('id')->first();
    }

    private function payloadEdit(Transaction $trx, int $qty): array
    {
        return [
            'tgl' => $trx->tgl->format('Y-m-d\TH:i'), 'platform_id' => $this->platform->id, 'alasan' => 'tambah barang setelah lunas',
            'items' => [['id' => $trx->items->first()->id, 'tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => $qty, 'harga' => 55000, 'diskon' => 0]],
        ];
    }

    public function test_kasir_edit_lunas_membuat_pengajuan_tanpa_menerapkan(): void
    {
        $trx = $this->jualLunas();
        $this->assertSame(9, $this->part->fresh()->stok);

        $this->actingAs($this->kasir)->put(route('transactions.update', $trx), $this->payloadEdit($trx, 3))->assertRedirect();

        $this->assertSame(1, EditRequest::pending()->count());
        $this->assertSame('edit', EditRequest::first()->jenis);
        $this->assertSame(1, $trx->fresh()->items->first()->qty); // belum berubah
        $this->assertSame(9, $this->part->fresh()->stok); // stok belum berubah
    }

    public function test_admin_setujui_menerapkan_perubahan(): void
    {
        $trx = $this->jualLunas();
        $this->actingAs($this->kasir)->put(route('transactions.update', $trx), $this->payloadEdit($trx, 3));
        $req = EditRequest::first();

        $this->actingAs($this->admin)->post(route('approvals.approve', $req))->assertRedirect();

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertSame($this->admin->id, $req->approved_by);
        $this->assertSame(3, $trx->fresh()->items->first()->qty);
        $this->assertSame(7, $this->part->fresh()->stok); // 9 - 2 tambahan
    }

    public function test_kasir_ajukan_batal_lalu_admin_setujui(): void
    {
        $trx = $this->jualLunas();

        $this->actingAs($this->kasir)->delete(route('transactions.cancel', $trx), ['alasan_batal' => 'salah input'])->assertRedirect();
        $this->assertSame('lunas', $trx->fresh()->status); // belum dibatalkan
        $this->assertSame('batal', EditRequest::first()->jenis);

        $this->actingAs($this->admin)->post(route('approvals.approve', EditRequest::first()))->assertRedirect();
        $this->assertSame('batal', $trx->fresh()->status);
        $this->assertSame(10, $this->part->fresh()->stok); // stok kembali
    }

    public function test_admin_tolak_tidak_mengubah(): void
    {
        $trx = $this->jualLunas();
        $this->actingAs($this->kasir)->put(route('transactions.update', $trx), $this->payloadEdit($trx, 3));
        $req = EditRequest::first();

        $this->actingAs($this->admin)->post(route('approvals.reject', $req), ['catatan' => 'tidak sesuai'])->assertRedirect();

        $req->refresh();
        $this->assertSame('ditolak', $req->status);
        $this->assertSame('tidak sesuai', $req->catatan);
        $this->assertSame(1, $trx->fresh()->items->first()->qty); // tak berubah
    }

    public function test_kasir_tak_bisa_akses_approvals(): void
    {
        $this->actingAs($this->kasir)->get(route('approvals.index'))->assertForbidden();
    }
}
