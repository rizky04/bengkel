<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $guarded = [];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
