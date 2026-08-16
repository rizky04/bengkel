<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id();
            $t->string('no')->unique();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->string('alasan');
            $t->decimal('total', 12, 2)->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('tgl')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $t->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('part_id')->nullable();
            $t->string('nama');
            $t->integer('qty');
            $t->decimal('harga_beli', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
