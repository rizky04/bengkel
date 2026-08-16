<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = ['harga' => 'float', 'subtotal' => 'float'];

    public function txItem()
    {
        return $this->belongsTo(TxItem::class, 'tx_item_id');
    }
}
