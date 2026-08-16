<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Part;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $b = current_branch();
        $trx = fn () => Transaction::where('branch_id', $b)->where('status', '!=', 'batal');

        $omzetHari = $trx()->whereDate('tgl', $today)->sum('total');
        $trxHari = $trx()->whereDate('tgl', $today)->count();
        $omzetBulan = $trx()->where('tgl', '>=', $monthStart)->sum('total');
        $pengeluaranBulan = Expense::where('branch_id', $b)->where('tanggal', '>=', $monthStart)->sum('nominal');

        $orderAktif = Transaction::where('branch_id', $b)->whereIn('status', ['antri', 'dikerjakan'])->count();
        $lowStock = Part::withStok($b)->lowStock($b)->orderBy('stok')->limit(10)->get();

        // omzet 7 hari
        $chart = collect(range(6, 0))->map(function ($d) use ($trx) {
            $day = Carbon::today()->subDays($d);
            return [
                'label' => $day->isoFormat('dd D/M'),
                'total' => (float) $trx()->whereDate('tgl', $day)->sum('total'),
            ];
        });

        $piutang = $trx()->get()->sum(fn ($t) => max(0, $t->sisa));

        return view('dashboard', compact(
            'omzetHari', 'trxHari', 'omzetBulan', 'pengeluaranBulan',
            'orderAktif', 'lowStock', 'chart', 'piutang'
        ));
    }
}
