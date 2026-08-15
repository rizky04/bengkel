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
}
