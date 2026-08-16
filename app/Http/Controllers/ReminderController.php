<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $defaultInterval = (int) Setting::get('servis_interval_hari', '90');
        // batas "akan datang": tampilkan yang jatuh tempo ≤ N hari ke depan (default 14) + yang lewat
        $lookahead = (int) $request->get('lookahead', 14);
        $batas = Carbon::today()->addDays($lookahead);

        // servis terakhir per kendaraan (transaksi tipe servis, tidak batal)
        $vehicles = Vehicle::with('customer')
            ->select('vehicles.*')
            ->selectSub(
                \App\Models\Transaction::selectRaw('MAX(tgl)')
                    ->whereColumn('transactions.vehicle_id', 'vehicles.id')
                    ->where('tipe', 'servis')->where('status', '!=', 'batal'),
                'servis_terakhir'
            )
            ->get()
            ->map(function ($v) use ($defaultInterval) {
                $v->interval = $v->servis_interval_hari ?: $defaultInterval;
                $v->jatuh_tempo = $v->servis_terakhir
                    ? Carbon::parse($v->servis_terakhir)->addDays($v->interval)
                    : null;
                return $v;
            })
            ->filter(fn ($v) => $v->jatuh_tempo && $v->jatuh_tempo->lte($batas))
            ->sortBy('jatuh_tempo')
            ->values();

        return view('reminders.index', compact('vehicles', 'defaultInterval', 'lookahead'));
    }
}
