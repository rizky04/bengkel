<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Master data ----
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('hp')->nullable();
            $t->text('alamat')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('plat');
            $t->string('jenis')->default('motor'); // motor | mobil
            $t->string('merk')->nullable();
            $t->string('tipe')->nullable();
            $t->year('tahun')->nullable();
            $t->string('no_rangka')->nullable();
            $t->string('no_mesin')->nullable();
            $t->string('warna')->nullable();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('tipe')->default('part'); // part | jasa
            $t->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('hp')->nullable();
            $t->text('alamat')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamps();
        });

        Schema::create('parts', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('nama');
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('satuan')->default('pcs');
            $t->decimal('harga_beli', 12, 2)->default(0);
            $t->decimal('harga_jual', 12, 2)->default(0);
            $t->integer('stok')->default(0);
            $t->integer('stok_min')->default(0);
            $t->string('lokasi_rak')->nullable();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('tarif', 12, 2)->default(0);
            $t->timestamps();
        });

        // ---- Penjualan / channel & promo ----
        Schema::create('platforms', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('promos', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('kode')->nullable();
            $t->string('jenis')->default('persen'); // persen | nominal | harga_khusus
            $t->decimal('nilai', 12, 2)->default(0);
            $t->string('cakupan')->default('semua'); // semua | kategori | item | platform
            $t->unsignedBigInteger('target_id')->nullable();
            $t->decimal('min_belanja', 12, 2)->nullable();
            $t->integer('min_qty')->nullable();
            $t->dateTime('mulai')->nullable();
            $t->dateTime('selesai')->nullable();
            $t->integer('kuota')->nullable();
            $t->integer('terpakai')->default(0);
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        // ---- Transaksi (servis + penjualan) ----
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->string('no_nota')->unique();
            $t->string('tipe')->default('penjualan'); // servis | penjualan
            $t->foreignId('platform_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('mekanik_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('keluhan')->nullable();
            $t->text('catatan_mekanik')->nullable();
            $t->string('status')->default('selesai'); // antri|dikerjakan|selesai|lunas|batal
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('diskon', 12, 2)->default(0);
            $t->foreignId('promo_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('pajak', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // kasir
            $t->timestamp('tgl')->nullable();
            $t->timestamps();
        });

        Schema::create('tx_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $t->string('tipe')->default('part'); // jasa | part
            $t->unsignedBigInteger('ref_id')->nullable(); // part_id / service_id
            $t->string('nama');
            $t->integer('qty')->default(1);
            $t->decimal('harga', 12, 2)->default(0);
            $t->decimal('diskon', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $t->decimal('jumlah', 12, 2)->default(0);
            $t->string('metode')->default('tunai'); // tunai|transfer|qris|kartu
            $t->timestamp('tgl_bayar')->nullable();
            $t->timestamps();
        });

        // ---- Pembelian & stok ----
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('total', 12, 2)->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('tgl')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('part_id')->constrained()->cascadeOnDelete();
            $t->integer('qty')->default(1);
            $t->decimal('harga_beli', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('stock_moves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->constrained()->cascadeOnDelete();
            $t->string('tipe'); // in | out | adjust
            $t->integer('qty');
            $t->integer('saldo'); // stok setelah mutasi
            $t->string('ref_tipe')->nullable(); // transaction | purchase | opname
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('keterangan')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('tgl')->nullable();
            $t->timestamps();
        });

        // ---- Keuangan ----
        Schema::create('expense_cats', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('nama');
            $t->string('jabatan')->nullable();
            $t->decimal('gaji_pokok', 12, 2)->default(0);
            $t->decimal('komisi_persen', 5, 2)->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal');
            $t->foreignId('expense_cat_id')->nullable()->constrained('expense_cats')->nullOnDelete();
            $t->decimal('nominal', 12, 2)->default(0);
            $t->string('keterangan')->nullable();
            $t->string('metode')->default('tunai');
            $t->string('bukti')->nullable();
            $t->string('ref_tipe')->nullable(); // salary | purchase
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('salaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('periode'); // 2026-08
            $t->decimal('gaji_pokok', 12, 2)->default(0);
            $t->decimal('bonus', 12, 2)->default(0);
            $t->decimal('komisi', 12, 2)->default(0);
            $t->decimal('potongan', 12, 2)->default(0);
            $t->decimal('total_dibayar', 12, 2)->default(0);
            $t->date('tgl_bayar')->nullable();
            $t->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        // ---- Settings ----
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'settings', 'salaries', 'expenses', 'employees', 'expense_cats',
            'stock_moves', 'purchase_items', 'purchases',
            'payments', 'tx_items', 'transactions',
            'promos', 'platforms', 'services', 'parts', 'suppliers',
            'categories', 'vehicles', 'customers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
