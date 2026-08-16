<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $defaultInterval = (int) Setting::get('servis_interval_hari', '90');
        $lookahead = (int) $request->get('lookahead', 14);
        $batas = Carbon::today()->addDays($lookahead);

        return Vehicle::with('customer:id,nama,hp')
            ->select('vehicles.*')
            ->selectSub(
                Transaction::selectRaw('MAX(tgl)')
                    ->whereColumn('transactions.vehicle_id', 'vehicles.id')
                    ->where('tipe', 'servis')->where('status', '!=', 'batal'),
                'servis_terakhir'
            )
            ->get()
            ->map(function ($v) use ($defaultInterval) {
                $interval = $v->servis_interval_hari ?: $defaultInterval;
                $jatuhTempo = $v->servis_terakhir ? Carbon::parse($v->servis_terakhir)->addDays($interval) : null;

                return [
                    'id' => $v->id,
                    'plat' => $v->plat,
                    'merk' => $v->merk,
                    'tipe' => $v->tipe,
                    'pemilik' => $v->customer?->nama,
                    'hp' => $v->customer?->hp,
                    'servis_terakhir' => $v->servis_terakhir,
                    'jatuh_tempo' => $jatuhTempo?->toDateString(),
                    'telat' => $jatuhTempo ? $jatuhTempo->isPast() : false,
                ];
            })
            ->filter(fn ($v) => $v['jatuh_tempo'] && Carbon::parse($v['jatuh_tempo'])->lte($batas))
            ->sortBy('jatuh_tempo')
            ->values();
    }
}
