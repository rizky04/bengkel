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

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class, 'tx_item_id');
    }

    /** Qty item ini yang sudah diretur. */
    public function qtyDiretur(): int
    {
        return (int) $this->returnItems()->sum('qty');
    }

    /** Sisa qty yang masih bisa diretur. */
    public function sisaRetur(): int
    {
        return $this->qty - $this->qtyDiretur();
    }
}

