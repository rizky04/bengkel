<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = [];

    protected $casts = ['total' => 'float', 'tgl' => 'datetime'];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function nomorBaru(): string
    {
        return 'RPB' . now()->format('ymd') . str_pad(
            (string) (static::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT
        );
    }
}
