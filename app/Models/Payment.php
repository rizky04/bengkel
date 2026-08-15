<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    protected $casts = ['tgl_bayar' => 'datetime', 'jumlah' => 'float'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
