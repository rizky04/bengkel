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

        $omzetHari = Transaction::whereDate('tgl', $today)->where('status', '!=', 'batal')->sum('total');
        $trxHari = Transaction::whereDate('tgl', $today)->where('status', '!=', 'batal')->count();
        $omzetBulan = Transaction::where('tgl', '>=', $monthStart)->where('status', '!=', 'batal')->sum('total');
        $pengeluaranBulan = Expense::where('tanggal', '>=', $monthStart)->sum('nominal');

        $orderAktif = Transaction::whereIn('status', ['antri', 'dikerjakan'])->count();
        $lowStock = Part::lowStock()->orderBy('stok')->limit(10)->get();

        // omzet 7 hari
        $chart = collect(range(6, 0))->map(function ($d) {
            $day = Carbon::today()->subDays($d);
            return [
                'label' => $day->isoFormat('dd D/M'),
                'total' => (float) Transaction::whereDate('tgl', $day)->where('status', '!=', 'batal')->sum('total'),
            ];
        });

        $piutang = Transaction::where('status', '!=', 'batal')
            ->get()->sum(fn ($t) => max(0, $t->sisa));

        return view('dashboard', compact(
            'omzetHari', 'trxHari', 'omzetBulan', 'pengeluaranBulan',
            'orderAktif', 'lowStock', 'chart', 'piutang'
        ));
    }
}
