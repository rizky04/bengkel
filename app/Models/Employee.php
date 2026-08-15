<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

    protected $casts = ['gaji_pokok' => 'float', 'komisi_persen' => 'float', 'aktif' => 'boolean'];

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
