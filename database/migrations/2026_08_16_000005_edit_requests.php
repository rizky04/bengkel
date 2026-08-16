<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edit_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('jenis'); // edit | batal
            $t->text('payload')->nullable(); // JSON usulan item+meta (untuk edit)
            $t->string('alasan');
            $t->string('status')->default('pending'); // pending | approved | ditolak
            $t->foreignId('user_id')->constrained(); // pengaju
            $t->unsignedBigInteger('approved_by')->nullable(); // penyetuju
            $t->string('catatan')->nullable(); // catatan admin (mis. alasan tolak)
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edit_requests');
    }
};
