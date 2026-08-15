<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $guarded = [];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'nilai' => 'float',
        'aktif' => 'boolean',
    ];

    /** Promo yang sedang berlaku (aktif, dalam periode, kuota belum habis). */
    public function scopeBerlaku($q)
    {
        $now = now();

        return $q->where('aktif', true)
            ->where(fn ($w) => $w->whereNull('mulai')->orWhere('mulai', '<=', $now))
            ->where(fn ($w) => $w->whereNull('selesai')->orWhere('selesai', '>=', $now))
            ->where(fn ($w) => $w->whereNull('kuota')->orWhereColumn('terpakai', '<', 'kuota'));
    }

    /** Besar potongan untuk subtotal tertentu; 0 bila syarat tak terpenuhi. */
    public function potongan(float $subtotal): float
    {
        if ($this->min_belanja && $subtotal < $this->min_belanja) {
            return 0;
        }

        $potong = match ($this->jenis) {
            'persen' => $subtotal * $this->nilai / 100,
            'nominal' => $this->nilai,
            'harga_khusus' => max(0, $subtotal - $this->nilai),
            default => 0,
        };

        return round(min($potong, $subtotal), 2); // tak boleh melebihi subtotal
    }
}
