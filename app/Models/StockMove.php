<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMove extends Model
{
    protected $guarded = [];

    protected $casts = ['tgl' => 'datetime'];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
