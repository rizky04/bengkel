<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = ['harga_beli' => 'float', 'subtotal' => 'float'];
}
