<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExpenseCat;
use App\Models\Platform;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bengkel.test'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'aktif' => true]
        );
        User::updateOrCreate(
            ['email' => 'kasir@bengkel.test'],
            ['name' => 'Kasir', 'password' => Hash::make('password'), 'role' => 'kasir', 'aktif' => true]
        );

        foreach (['Kasir', 'Shopee', 'Tokopedia', 'WhatsApp'] as $p) {
            Platform::firstOrCreate(['nama' => $p]);
        }

        foreach (['Oli', 'Ban', 'Aki', 'Kampas Rem', 'Sparepart Umum'] as $c) {
            Category::firstOrCreate(['nama' => $c, 'tipe' => 'part']);
        }
        foreach (['Servis Ringan', 'Servis Berat', 'Ganti Oli', 'Tune Up'] as $c) {
            Category::firstOrCreate(['nama' => $c, 'tipe' => 'jasa']);
        }

        foreach (['Gaji', 'Sewa Tempat', 'Listrik & Air', 'Beli Alat', 'Konsumsi', 'Transport', 'Marketing', 'Lain-lain'] as $c) {
            ExpenseCat::firstOrCreate(['nama' => $c]);
        }

        $settings = [
            'nama_bengkel' => 'Bengkel Jaya Motor',
            'alamat' => 'Jl. Contoh No. 1',
            'hp' => '08123456789',
            'pajak_aktif' => '0',
            'pajak_persen' => '0',
            'nota_prefix' => 'INV',
        ];
        foreach ($settings as $k => $v) {
            Setting::put($k, $v);
        }
    }
}
