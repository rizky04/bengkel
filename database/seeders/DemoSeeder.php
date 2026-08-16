<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCat;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Platform;
use App\Models\Promo;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Data dummy untuk coba-coba. Jalankan: php artisan db:seed --class=DemoSeeder
 * Aman ditambahkan ke DB yang sudah ada (idempoten via firstOrCreate untuk master data).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $branch = Branch::firstOrCreate(['nama' => 'Pusat'], ['aktif' => true]);
        $bid = $branch->id;

        // ── Users tambahan ──
        User::firstOrCreate(['email' => 'mekanik@bengkel.test'], ['name' => 'Budi Mekanik', 'password' => Hash::make('password'), 'role' => 'mekanik', 'aktif' => true, 'branch_id' => $bid]);
        $mekanikUser = User::where('role', 'mekanik')->first() ?? User::where('role', 'admin')->first();

        // ── Supplier ──
        $suppliers = collect(['Maju Jaya Parts', 'Sumber Rejeki Motor', 'Anugrah Sparepart'])
            ->map(fn ($n) => Supplier::firstOrCreate(['nama' => $n], ['hp' => $faker->numerify('08##########')]));

        // ── Sparepart (stok generous di Pusat) ──
        $katPart = Category::where('tipe', 'part')->pluck('id');
        $partData = [
            ['Oli Mesin Yamalube 1L', 45000, 55000], ['Oli Shell AX7 1L', 55000, 68000],
            ['Kampas Rem Depan', 35000, 55000], ['Kampas Rem Belakang', 30000, 48000],
            ['Busi NGK CPR', 18000, 28000], ['Aki GS Astra', 220000, 285000],
            ['Ban Luar 80/90-14', 130000, 180000], ['Ban Dalam 14', 22000, 35000],
            ['Rantai + Gir Set', 180000, 260000], ['Filter Udara', 25000, 42000],
            ['Lampu Depan LED', 65000, 95000], ['V-Belt', 85000, 130000],
            ['Roller CVT', 40000, 65000], ['Kampas Kopling', 90000, 140000],
            ['Seal Shock', 28000, 45000],
        ];
        $parts = collect($partData)->map(function ($p, $i) use ($katPart, $suppliers, $bid) {
            $part = Part::firstOrCreate(
                ['kode' => 'SP' . str_pad($i + 100, 4, '0', STR_PAD_LEFT)],
                ['nama' => $p[0], 'category_id' => $katPart->random(), 'satuan' => 'pcs',
                 'harga_beli' => $p[1], 'harga_jual' => $p[2], 'stok_min' => 5,
                 'supplier_id' => $suppliers->random()->id, 'lokasi_rak' => 'Rak ' . chr(65 + ($i % 5))]
            );
            if ($part->stokDi($bid) === 0) {
                $part->moveStock($bid, 'in', 100, ['tipe' => 'stok_awal', 'keterangan' => 'Stok awal demo']);
            }
            return $part;
        });

        // ── Jasa ──
        $katJasa = Category::where('tipe', 'jasa')->pluck('id');
        $services = collect([
            ['Servis Ringan', 45000], ['Servis Besar', 120000], ['Ganti Oli', 20000],
            ['Tune Up', 85000], ['Ganti Ban', 15000], ['Setel Rem', 25000], ['Cuci Motor', 20000],
        ])->map(fn ($s) => Service::firstOrCreate(['nama' => $s[0]], ['category_id' => $katJasa->random(), 'tarif' => $s[1]]));

        // ── Pelanggan + kendaraan ──
        $merkMotor = ['Honda Vario', 'Yamaha NMAX', 'Honda Beat', 'Yamaha Mio', 'Honda PCX', 'Suzuki Satria'];
        $customers = collect(range(1, 25))->map(function () use ($faker, $merkMotor) {
            $c = Customer::create(['nama' => $faker->name(), 'hp' => $faker->numerify('08##########'), 'alamat' => $faker->city()]);
            foreach (range(1, rand(1, 2)) as $k) {
                Vehicle::create([
                    'customer_id' => $c->id, 'plat' => 'B ' . rand(1000, 9999) . ' ' . strtoupper($faker->lexify('??')),
                    'jenis' => 'motor', 'merk' => $faker->randomElement($merkMotor), 'tipe' => rand(2015, 2024),
                    'tahun' => rand(2015, 2024), 'servis_interval_hari' => $faker->randomElement([60, 90, 90, 120]),
                ]);
            }
            return $c;
        });

        // ── Promo + voucher ──
        Promo::firstOrCreate(['nama' => 'Diskon Servis 10%'], ['jenis' => 'persen', 'nilai' => 10, 'aktif' => true]);
        Promo::firstOrCreate(['kode' => 'HEMAT20'], ['nama' => 'Voucher Hemat 20rb', 'jenis' => 'nominal', 'nilai' => 20000, 'min_belanja' => 100000, 'kuota' => 50, 'aktif' => true]);

        // ── Karyawan ──
        Employee::firstOrCreate(['nama' => 'Budi Santoso'], ['jabatan' => 'Mekanik', 'gaji_pokok' => 3000000, 'komisi_persen' => 5, 'user_id' => $mekanikUser?->id, 'aktif' => true]);
        Employee::firstOrCreate(['nama' => 'Andi Wijaya'], ['jabatan' => 'Kasir', 'gaji_pokok' => 2500000, 'aktif' => true]);

        // ── Transaksi (60 hari terakhir) ──
        $platforms = Platform::pluck('id');
        $vehicles = Vehicle::with('customer')->get();
        $admin = User::where('role', 'admin')->first();
        \Illuminate\Support\Facades\Auth::login($admin);

        foreach (range(1, 60) as $n) {
            $tgl = Carbon::now()->subDays(rand(0, 60))->setTime(rand(8, 17), rand(0, 59));
            $servis = rand(0, 1) === 1;
            $veh = $vehicles->random();

            \DB::transaction(function () use ($servis, $veh, $tgl, $parts, $services, $platforms, $bid, $mekanikUser, $faker) {
                $subtotal = 0; $rows = [];
                if ($servis) {
                    $svc = $services->random();
                    $rows[] = ['tipe' => 'jasa', 'ref_id' => $svc->id, 'nama' => $svc->nama, 'qty' => 1, 'harga' => $svc->tarif];
                    $subtotal += $svc->tarif;
                }
                foreach (range(1, rand($servis ? 0 : 1, 3)) as $k) {
                    $p = $parts->random();
                    $qty = rand(1, 2);
                    $rows[] = ['tipe' => 'part', 'ref_id' => $p->id, 'nama' => $p->nama, 'qty' => $qty, 'harga' => $p->harga_jual];
                    $subtotal += $qty * $p->harga_jual;
                }
                if (! $rows) {
                    return;
                }

                $status = $servis ? $faker->randomElement(['selesai', 'lunas', 'lunas', 'dikerjakan']) : 'lunas';
                $trx = Transaction::create([
                    'no_nota' => Transaction::nomorBaru(), 'branch_id' => $bid,
                    'tipe' => $servis ? 'servis' : 'penjualan', 'platform_id' => $platforms->random(),
                    'customer_id' => $servis ? $veh->customer_id : null, 'vehicle_id' => $servis ? $veh->id : null,
                    'mekanik_id' => $servis ? $mekanikUser?->id : null,
                    'keluhan' => $servis ? $faker->randomElement(['Motor brebet', 'Rem blong', 'Servis rutin', 'Ganti oli']) : null,
                    'status' => $status, 'subtotal' => $subtotal, 'diskon' => 0, 'pajak' => 0, 'total' => $subtotal,
                    'user_id' => auth()->id(), 'tgl' => $tgl,
                ]);
                foreach ($rows as $r) {
                    $trx->items()->create([...$r, 'diskon' => 0, 'subtotal' => $r['qty'] * $r['harga']]);
                    if ($r['tipe'] === 'part') {
                        Part::find($r['ref_id'])->moveStock($bid, 'out', $r['qty'], ['tipe' => 'transaction', 'id' => $trx->id, 'keterangan' => 'Penjualan ' . $trx->no_nota]);
                    }
                }
                if (in_array($status, ['lunas', 'selesai'])) {
                    $bayar = $status === 'lunas' ? $subtotal : $subtotal; // dianggap dibayar
                    if ($status === 'lunas') {
                        Payment::create(['transaction_id' => $trx->id, 'branch_id' => $bid, 'jumlah' => $subtotal, 'metode' => $faker->randomElement(['tunai', 'tunai', 'transfer', 'qris']), 'tgl_bayar' => $tgl]);
                    }
                }
            });
        }

        // ── Pembelian ──
        foreach (range(1, 6) as $n) {
            $tgl = Carbon::now()->subDays(rand(0, 60));
            \DB::transaction(function () use ($parts, $suppliers, $tgl, $bid, $faker) {
                $pur = Purchase::create(['no' => 'PB' . $tgl->format('ymd') . rand(100, 999), 'branch_id' => $bid, 'supplier_id' => $suppliers->random()->id, 'tgl' => $tgl, 'status' => $faker->randomElement(['lunas', 'lunas', 'belum_lunas']), 'total' => 0, 'user_id' => auth()->id()]);
                $total = 0;
                foreach ($parts->random(rand(2, 4)) as $p) {
                    $qty = rand(10, 30);
                    $pur->items()->create(['part_id' => $p->id, 'qty' => $qty, 'harga_beli' => $p->harga_beli, 'subtotal' => $qty * $p->harga_beli]);
                    $p->moveStock($bid, 'in', $qty, ['tipe' => 'purchase', 'id' => $pur->id, 'keterangan' => 'Pembelian ' . $pur->no]);
                    $total += $qty * $p->harga_beli;
                }
                $pur->update(['total' => $total]);
            });
        }

        // ── Pengeluaran ──
        $cats = ExpenseCat::pluck('id', 'nama');
        foreach (range(1, 15) as $n) {
            Expense::create([
                'branch_id' => $bid, 'tanggal' => Carbon::now()->subDays(rand(0, 60))->toDateString(),
                'expense_cat_id' => $cats->random(), 'nominal' => rand(1, 8) * 50000,
                'keterangan' => $faker->randomElement(['Listrik', 'Air', 'Konsumsi', 'Beli kunci', 'Transport', 'ATK']),
                'metode' => 'tunai', 'user_id' => auth()->id(),
            ]);
        }

        \Illuminate\Support\Facades\Auth::logout();

        $this->command->info('Demo data dibuat: 25 pelanggan, 15 sparepart, 60 transaksi, 6 pembelian, 15 pengeluaran.');
    }
}
