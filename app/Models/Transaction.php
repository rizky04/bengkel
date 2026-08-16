<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tgl' => 'datetime',
        'subtotal' => 'float',
        'diskon' => 'float',
        'pajak' => 'float',
        'total' => 'float',
    ];

    public const STATUS = ['antri', 'dikerjakan', 'selesai', 'lunas', 'batal'];

    public static function nomorBaru(): string
    {
        $prefix = Setting::get('nota_prefix', 'INV');

        return $prefix . now()->format('ymd') . str_pad(
            (string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT
        );
    }

    public function scopeAktif($q)
    {
        return $q->where('status', '!=', 'batal');
    }

    public function items()
    {
        return $this->hasMany(TxItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mekanik()
    {
        return $this->belongsTo(User::class, 'mekanik_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    /** Total nilai yang sudah diretur. */
    public function totalRetur(): float
    {
        return (float) $this->returns()->sum('total');
    }

    /** Terkunci = sudah final (lunas / dibatalkan). Edit hanya oleh admin/owner. */
    public function terkunci(): bool
    {
        return in_array($this->status, ['lunas', 'batal'], true);
    }

    public function getDibayarAttribute(): float
    {
        return (float) $this->payments->sum('jumlah');
    }

    public function getSisaAttribute(): float
    {
        return round($this->total - $this->dibayar, 2);
    }
}
