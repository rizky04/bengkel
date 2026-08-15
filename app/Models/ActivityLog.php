<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Catat satu aktivitas (dipanggil dari observer/controller). */
    public static function catat(string $aksi, ?string $deskripsi = null, ?string $refTipe = null, $refId = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ref_tipe' => $refTipe,
            'ref_id' => $refId,
            'created_at' => now(),
        ]);
    }
}
