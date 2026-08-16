<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\Platform;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Skema "open ticket": kasir edit saat terbuka, admin saja saat lunas / batal. */
class EditAksesTest extends TestCase
{
    use RefreshDatabase;

    private Part $part;
    private Platform $platform;
    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->part = $this->makePart(['kode' => 'SP1', 'nama' => 'Oli'], stok: 10);
        $this->platform = Platform::create(['nama' => 'Kasir']);

        // role kasir uji: punya 'transactions' tapi TIDAK punya 'transactions_edit'
        $role = Role::create(['key' => 'kasir_open', 'nama' => 'Kasir Uji', 'is_admin' => false]);
        foreach (['pos', 'transactions'] as $p) {
            $role->permissions()->create(['permission' => $p]);
        }
        $this->kasir = User::create(['name' => 'K', 'email' => 'k@b.test', 'password' => bcrypt('x'), 'role' => 'kasir_open', 'aktif' => true, 'branch_id' => $this->branch()->id]);
    }

    private function jual(string $bayar): Transaction
    {
        $this->actingAs($this->kasir)->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $this->platform->id, 'metode' => 'tunai', 'bayar' => $bayar,
            'items' => [['tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0]],
        ]);

        return Transaction::latest('id')->first();
    }

    private function payload(Transaction $trx, int $qty): array
    {
        return [
            'tgl' => $trx->tgl->format('Y-m-d\TH:i'), 'platform_id' => $this->platform->id,
            'items' => [['id' => $trx->items->first()->id, 'tipe' => 'part', 'ref_id' => $this->part->id, 'nama' => 'Oli', 'qty' => $qty, 'harga' => 55000, 'diskon' => 0]],
        ];
    }

    public function test_kasir_boleh_edit_transaksi_terbuka(): void
    {
        $trx = $this->jual('0'); // belum bayar → status 'selesai' (terbuka)
        $this->assertFalse($trx->terkunci());

        $this->actingAs($this->kasir)
            ->put(route('transactions.update', $trx), $this->payload($trx, 3))
            ->assertRedirect();

        $this->assertSame(3, $trx->fresh()->items->first()->qty);
    }

    // Perilaku transaksi terkunci untuk kasir (jadi pengajuan) diuji di ApprovalTest.

    public function test_admin_boleh_edit_transaksi_lunas(): void
    {
        $trx = $this->jual('55000');
        Role::firstOrCreate(['key' => 'admin'], ['nama' => 'Admin', 'is_admin' => true]);
        $admin = User::create(['name' => 'A', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id]);

        $this->actingAs($admin)
            ->put(route('transactions.update', $trx), $this->payload($trx, 2))
            ->assertRedirect();

        $this->assertSame(2, $trx->fresh()->items->first()->qty);
    }
}
