<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

    protected $casts = ['tarif' => 'float'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
