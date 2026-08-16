<?php

use App\Support\Acl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();      // slug penghubung ke users.role
            $t->string('nama');               // label tampilan
            $t->boolean('is_admin')->default(false); // akses penuh (bypass izin)
            $t->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->string('permission');
            $t->unique(['role_id', 'permission']);
        });

        // Seed role dari nilai users.role yang sudah ada + role bawaan
        $keys = collect(['admin', 'kasir', 'mekanik'])
            ->merge(DB::table('users')->distinct()->pluck('role'))
            ->filter()->unique();

        foreach ($keys as $key) {
            $roleId = DB::table('roles')->insertGetId([
                'key' => $key,
                'nama' => ucfirst($key),
                'is_admin' => $key === 'admin',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            if ($key !== 'admin') {
                foreach (Acl::preset($key) as $perm) {
                    DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission' => $perm]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
