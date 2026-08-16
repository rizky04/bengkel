<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\Platform;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Kasir', 'email' => 'k@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id]);
        $this->actingAs($this->user);
    }

    public function test_buka_shift_dan_tidak_bisa_dobel(): void
    {
        $this->post(route('shifts.store'), ['kas_awal' => 100000])->assertRedirect();
        $this->assertSame(1, Shift::where('status', 'buka')->count());

        // buka kedua ditolak
        $this->post(route('shifts.store'), ['kas_awal' => 50000])->assertSessionHas('error');
        $this->assertSame(1, Shift::count());
    }

    public function test_tutup_shift_menghitung_selisih(): void
    {
        // jual tunai 55rb dalam shift
        $platform = Platform::create(['nama' => 'Kasir']);
        $part = $this->makePart(['kode' => 'P1', 'nama' => 'Oli', 'stok_min' => 1], stok: 10);

        $this->post(route('shifts.store'), ['kas_awal' => 100000]);
        $shift = Shift::first();

        $this->post(route('pos.store'), [
            'tipe' => 'penjualan', 'platform_id' => $platform->id, 'metode' => 'tunai', 'bayar' => 55000,
            'items' => [['tipe' => 'part', 'ref_id' => $part->id, 'nama' => 'Oli', 'qty' => 1, 'harga' => 55000, 'diskon' => 0]],
        ]);

        // kas seharusnya = 100rb + 55rb = 155rb; fisik 150rb → selisih -5rb
        $this->patch(route('shifts.close', $shift), ['kas_akhir_fisik' => 150000])->assertRedirect();

        $shift->refresh();
        $this->assertSame('tutup', $shift->status);
        $this->assertSame(155000.0, $shift->kasSeharusnya());
        $this->assertSame(-5000.0, $shift->selisih());
    }

    public function test_aktivitas_tercatat(): void
    {
        $this->post(route('shifts.store'), ['kas_awal' => 100000]);

        $this->assertDatabaseHas('activity_logs', ['aksi' => 'buka_shift', 'user_id' => $this->user->id]);
    }
}
