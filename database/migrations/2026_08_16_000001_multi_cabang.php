<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('alamat')->nullable();
            $t->string('hp')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('inventories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('part_id')->constrained()->cascadeOnDelete();
            $t->integer('stok')->default(0);
            $t->timestamps();
            $t->unique(['branch_id', 'part_id']);
        });

        // branch_id di tabel operasional
        foreach (['stock_moves', 'transactions', 'purchases', 'expenses', 'shifts', 'users'] as $tbl) {
            Schema::table($tbl, function (Blueprint $t) {
                $t->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        // ── Migrasi data lama ke cabang default "Pusat" ──
        $pusatId = DB::table('branches')->insertGetId([
            'nama' => 'Pusat', 'aktif' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['stock_moves', 'transactions', 'purchases', 'expenses', 'shifts', 'users'] as $tbl) {
            DB::table($tbl)->update(['branch_id' => $pusatId]);
        }

        // pindahkan stok parts ke inventories cabang Pusat
        foreach (DB::table('parts')->get(['id', 'stok']) as $p) {
            DB::table('inventories')->insert([
                'branch_id' => $pusatId, 'part_id' => $p->id, 'stok' => $p->stok,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // parts.stok tak dipakai lagi (stok kini per cabang di inventories)
        Schema::table('parts', fn (Blueprint $t) => $t->dropColumn('stok'));
    }

    public function down(): void
    {
        Schema::table('parts', fn (Blueprint $t) => $t->integer('stok')->default(0));
        foreach (['stock_moves', 'transactions', 'purchases', 'expenses', 'shifts', 'users'] as $tbl) {
            Schema::table($tbl, function (Blueprint $t) {
                $t->dropConstrainedForeignId('branch_id');
            });
        }
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('branches');
    }
};
