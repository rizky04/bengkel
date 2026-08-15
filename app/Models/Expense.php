<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    protected $casts = ['tanggal' => 'date', 'nominal' => 'float'];

    public function category()
    {
        return $this->belongsTo(ExpenseCat::class, 'expense_cat_id');
    }
}
