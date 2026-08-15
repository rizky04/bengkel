<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    protected $casts = ['aktif' => 'boolean'];

    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }
}
