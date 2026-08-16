<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alasan pembatalan
        Schema::table('transactions', fn (Blueprint $t) => $t->string('alasan_batal')->nullable()->after('status'));

        // Status pembayaran pembelian ke supplier + alasan batal
        Schema::table('purchases', function (Blueprint $t) {
            $t->string('status')->default('lunas')->after('total'); // lunas | belum_lunas
            $t->string('alasan_batal')->nullable()->after('status');
        });

        // Retur penjualan/servis
        Schema::create('returns', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $t->string('alasan')->nullable();
            $t->decimal('total', 12, 2)->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('tgl')->nullable();
            $t->timestamps();
        });

        Schema::create('return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tx_item_id')->nullable()->constrained('tx_items')->nullOnDelete();
            $t->unsignedBigInteger('part_id')->nullable(); // hanya part yang restock
            $t->string('nama');
            $t->integer('qty');
            $t->decimal('harga', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::table('purchases', fn (Blueprint $t) => $t->dropColumn(['status', 'alasan_batal']));
        Schema::table('transactions', fn (Blueprint $t) => $t->dropColumn('alasan_batal'));
    }
};
