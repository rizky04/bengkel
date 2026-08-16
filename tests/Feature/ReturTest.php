<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\Platform;
use App\Models\SalesReturn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::create([
            'name' => 'A', 'email' => 'a@b.test', 'password' => bcrypt('x'),
            'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id,
        ]));
        $this->part = $this->makePart(['kode' => 'P1', 'nama' => 'Oli'], stok: 10);
        $this->platform = Platform::create(['nama' => 'Kasir']);
    }

    private function jual(int $qty = 3): Transaction
    {
        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id, 'metode' => 'tunai', 'bayar' => $qty * 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => $qty, 'harga' => 55000, 'diskon' => 0]],
        ]);

        return Transaction::latest('id')->first();
    }

    public function test_retur_mengembalikan_stok_dan_mencatat(): void
    {
        $trx = $this->jual(3);
        $this->assertSame(7, $this->part->fresh()->stok); // 10 - 3

        $item = $trx->items->first();
        $this->post(route('returns.store'), [
            'transaction_id' => $trx->id, 'alasan' => 'barang rusak',
            'items' => [['tx_item_id' => $item->id, 'qty' => 2]],
        ])->assertRedirect();

        $this->assertSame(9, $this->part->fresh()->stok); // 7 + 2 retur
        $retur = SalesReturn::first();
        $this->assertSame(110000.0, $retur->total); // 2 x 55rb
        $this->assertSame(2, $item->fresh()->qtyDiretur());
        $this->assertSame(1, $item->fresh()->sisaRetur());
    }

    public function test_retur_tidak_boleh_melebihi_sisa(): void
    {
        $trx = $this->jual(2);
        $item = $trx->items->first();

        // minta retur 5 padahal terjual 2 → dibatasi ke 2
        $this->post(route('returns.store'), [
            'transaction_id' => $trx->id, 'alasan' => 'x',
            'items' => [['tx_item_id' => $item->id, 'qty' => 5]],
        ]);

        $this->assertSame(2, SalesReturn::first()->items->first()->qty); // dibatasi
        $this->assertSame(10, $this->part->fresh()->stok); // 10 - 2 + 2
    }

    public function test_kasir_tanpa_izin_retur_ditolak(): void
    {
        // role custom tanpa izin 'returns'
        $role = \App\Models\Role::create(['key' => 'kasir_x', 'nama' => 'Kasir X', 'is_admin' => false]);
        $role->permissions()->create(['permission' => 'transactions']);
        $u = User::create(['name' => 'K', 'email' => 'k@b.test', 'password' => bcrypt('x'), 'role' => 'kasir_x', 'aktif' => true, 'branch_id' => $this->branch()->id]);

        $this->actingAs($u)->get(route('returns.index'))->assertForbidden();
    }
}
