<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $table = 'returns';
    protected $guarded = [];

    protected $casts = ['total' => 'float', 'tgl' => 'datetime'];

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function nomorBaru(): string
    {
        return 'RTR' . now()->format('ymd') . str_pad((string) (static::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}
