<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->decimal('kas_awal', 12, 2)->default(0);
            $t->decimal('kas_akhir_fisik', 12, 2)->nullable();
            $t->text('catatan')->nullable();
            $t->string('status')->default('buka'); // buka | tutup
            $t->timestamp('buka_at')->nullable();
            $t->timestamp('tutup_at')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('aksi');
            $t->string('deskripsi')->nullable();
            $t->string('ref_tipe')->nullable();
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('shifts');
    }
};
