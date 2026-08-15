<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxItem extends Model
{
    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
