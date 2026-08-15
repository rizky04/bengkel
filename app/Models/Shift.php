<?php

namespace App\Models;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'kas_awal' => 'float', 'kas_akhir_fisik' => 'float',
        'buka_at' => 'datetime', 'tutup_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Batas waktu perhitungan: dari buka sampai tutup (atau sekarang bila masih buka). */
    private function window(): array
    {
        return [$this->buka_at, $this->tutup_at ?? now()];
    }

    public function penjualanTunai(): float
    {
        return (float) Payment::where('metode', 'tunai')
            ->whereBetween('tgl_bayar', $this->window())->sum('jumlah');
    }

    public function pengeluaranTunai(): float
    {
        [$a, $b] = $this->window();

        return (float) Expense::where('metode', 'tunai')
            ->whereBetween('created_at', [$a, $b])->sum('nominal');
    }

    public function jumlahTransaksi(): int
    {
        return Transaction::aktif()->whereBetween('tgl', $this->window())
            ->where('user_id', $this->user_id)->count();
    }

    /** Kas seharusnya di laci: kas awal + tunai masuk − pengeluaran tunai. */
    public function kasSeharusnya(): float
    {
        return $this->kas_awal + $this->penjualanTunai() - $this->pengeluaranTunai();
    }

    public function selisih(): float
    {
        return ($this->kas_akhir_fisik ?? 0) - $this->kasSeharusnya();
    }
}
