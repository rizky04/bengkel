<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function returnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function qtyDiretur(): int
    {
        return (int) $this->returnItems()->sum('qty');
    }

    public function sisaRetur(): int
    {
        return $this->qty - $this->qtyDiretur();
    }
}
