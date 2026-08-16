<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Platform;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_kendaraan_jatuh_tempo_muncul_yang_belum_tidak(): void
    {
        Setting::put('servis_interval_hari', '90');
        $admin = User::create(['name' => 'A', 'email' => 'a@b.test', 'password' => bcrypt('x'), 'role' => 'admin', 'aktif' => true, 'branch_id' => $this->branch()->id]);
        $this->actingAs($admin);
        Platform::create(['nama' => 'Kasir']);

        $cust = Customer::create(['nama' => 'Budi', 'hp' => '081234']);
        $due = Vehicle::create(['customer_id' => $cust->id, 'plat' => 'B-DUE', 'jenis' => 'motor']);
        $fresh = Vehicle::create(['customer_id' => $cust->id, 'plat' => 'B-FRESH', 'jenis' => 'motor']);

        // servis 100 hari lalu (lewat tempo) & 5 hari lalu (belum)
        $this->servis($due, now()->subDays(100));
        $this->servis($fresh, now()->subDays(5));

        $res = $this->get(route('reminders.index'));
        $res->assertOk();
        $res->assertSee('B-DUE');
        $res->assertDontSee('B-FRESH');
    }

    private function servis(Vehicle $v, $tgl): void
    {
        Transaction::create([
            'no_nota' => 'INV' . uniqid(), 'branch_id' => $this->branch()->id,
            'tipe' => 'servis', 'customer_id' => $v->customer_id, 'vehicle_id' => $v->id,
            'status' => 'lunas', 'subtotal' => 50000, 'total' => 50000, 'tgl' => $tgl,
        ]);
    }
}
