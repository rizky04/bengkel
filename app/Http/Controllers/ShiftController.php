<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $aktif = Shift::where('user_id', auth()->id())->where('status', 'buka')->latest()->first();
        $shifts = Shift::with('user')->latest('buka_at')->paginate(15);

        return view('shifts.index', compact('aktif', 'shifts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['kas_awal' => 'required|numeric|min:0']);

        if (Shift::where('user_id', auth()->id())->where('status', 'buka')->exists()) {
            return back()->with('error', 'Masih ada shift yang terbuka. Tutup dulu.');
        }

        $shift = Shift::create([
            'user_id' => auth()->id(),
            'kas_awal' => $data['kas_awal'],
            'status' => 'buka',
            'buka_at' => now(),
        ]);
        ActivityLog::catat('buka_shift', 'Kas awal ' . rupiah($shift->kas_awal), 'shift', $shift->id);

        return back()->with('success', 'Shift dibuka.');
    }

    public function show(Shift $shift)
    {
        return view('shifts.show', compact('shift'));
    }

    public function close(Request $request, Shift $shift)
    {
        if ($shift->status === 'tutup') {
            return back()->with('warning', 'Shift sudah ditutup.');
        }

        $data = $request->validate([
            'kas_akhir_fisik' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:255',
        ]);

        $shift->update([
            'kas_akhir_fisik' => $data['kas_akhir_fisik'],
            'catatan' => $data['catatan'] ?? null,
            'status' => 'tutup',
            'tutup_at' => now(),
        ]);
        ActivityLog::catat('tutup_shift', 'Selisih ' . rupiah($shift->selisih()), 'shift', $shift->id);

        return redirect()->route('shifts.show', $shift)->with('success', 'Shift ditutup.');
    }
}
