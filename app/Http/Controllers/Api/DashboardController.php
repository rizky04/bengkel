<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $b = current_branch();
        $today = today();
        $omzet = (float) Transaction::aktif()->where('branch_id', $b)->whereDate('tgl', $today)->sum('total');
        $trxCount = Transaction::aktif()->where('branch_id', $b)->whereDate('tgl', $today)->count();
        $piutang = (float) Transaction::aktif()->where('branch_id', $b)
            ->whereIn('status', ['selesai', 'antri', 'dikerjakan'])
            ->sum(DB::raw('total - (select coalesce(sum(jumlah),0) from payments where payments.transaction_id = transactions.id)'));
        $lowStock = Part::withStok($b)->lowStock($b)->count();

        return compact('omzet', 'trxCount', 'piutang', 'lowStock');
    }
}
