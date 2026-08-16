<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            // interval servis berkala (hari); null = pakai default global di Setting
            $t->integer('servis_interval_hari')->nullable()->after('warna');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', fn (Blueprint $t) => $t->dropColumn('servis_interval_hari'));
    }
};
