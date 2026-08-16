<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index()
    {
        return Shift::with('user:id,name')->where('branch_id', current_branch())
            ->latest('buka_at')->limit(50)->get();
    }

    /** Shift terbuka milik user ini + kas seharusnya berjalan. */
    public function current()
    {
        $shift = Shift::where('user_id', auth()->id())->where('status', 'buka')->latest()->first();
        if (! $shift) {
            return ['shift' => null];
        }

        return ['shift' => $this->withHitungan($shift)];
    }

    public function open(Request $request)
    {
        $data = $request->validate(['kas_awal' => 'required|numeric|min:0']);
        if (Shift::where('user_id', auth()->id())->where('status', 'buka')->exists()) {
            throw ValidationException::withMessages(['kas_awal' => 'Masih ada shift terbuka. Tutup dulu.']);
        }
        $shift = Shift::create([
            'user_id' => auth()->id(),
            'branch_id' => current_branch(),
            'kas_awal' => $data['kas_awal'],
            'status' => 'buka',
            'buka_at' => now(),
        ]);
        ActivityLog::catat('buka_shift', 'Kas awal ' . rupiah($shift->kas_awal), 'shift', $shift->id);

        return $this->withHitungan($shift);
    }

    public function close(Request $request, Shift $shift)
    {
        if ($shift->status === 'tutup') {
            throw ValidationException::withMessages(['kas_akhir_fisik' => 'Shift sudah ditutup.']);
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

        return $this->withHitungan($shift->fresh());
    }

    public function show(Shift $shift)
    {
        return $this->withHitungan($shift);
    }

    private function withHitungan(Shift $shift): array
    {
        return [
            ...$shift->load('user:id,name')->toArray(),
            'penjualan_tunai' => $shift->penjualanTunai(),
            'pengeluaran_tunai' => $shift->pengeluaranTunai(),
            'jumlah_transaksi' => $shift->jumlahTransaksi(),
            'kas_seharusnya' => $shift->kasSeharusnya(),
            'selisih' => $shift->selisih(),
        ];
    }
}
