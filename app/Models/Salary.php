<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tgl_bayar' => 'date',
        'gaji_pokok' => 'float', 'bonus' => 'float', 'komisi' => 'float',
        'potongan' => 'float', 'total_dibayar' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
