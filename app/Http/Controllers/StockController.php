<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\StockMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $branchId = current_branch();
        $parts = Part::with('category')->withStok($branchId)
            ->when($q, fn ($query) => $query->where('nama', 'like', "%$q%")->orWhere('kode', 'like', "%$q%"))
            ->when($request->get('low'), fn ($query) => $query->lowStock($branchId))
            ->orderBy('nama')->paginate(20)->withQueryString();

        $nilaiPersediaan = (float) \App\Models\Inventory::where('branch_id', $branchId)
            ->join('parts', 'parts.id', '=', 'inventories.part_id')
            ->sum(DB::raw('inventories.stok * parts.harga_beli'));
        $jumlahMenipis = Part::lowStock($branchId)->count();

        return view('stock.index', compact('parts', 'q', 'nilaiPersediaan', 'jumlahMenipis'));
    }

    /** Kartu stok: riwayat mutasi satu part. */
    public function card(Request $request, Part $part)
    {
        $moves = $part->stockMoves()->with('user')
            ->where('branch_id', current_branch())
            ->when($request->get('dari'), fn ($q, $v) => $q->whereDate('tgl', '>=', $v))
            ->when($request->get('sampai'), fn ($q, $v) => $q->whereDate('tgl', '<=', $v))
            ->latest('tgl')->latest('id')->paginate(30)->withQueryString();

        return view('stock.card', compact('part', 'moves'));
    }

    public function opnameForm(Request $request)
    {
        $q = $request->get('q');
        $parts = Part::withStok()->when($q, fn ($query) => $query->where('nama', 'like', "%$q%")->orWhere('kode', 'like', "%$q%"))
            ->orderBy('nama')->limit(100)->get();

        return view('stock.opname', compact('parts', 'q'));
    }

    public function opnameStore(Request $request)
    {
        $data = $request->validate([
            'fisik' => 'required|array',
            'fisik.*' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $diubah = 0;
        $branchId = current_branch();
        DB::transaction(function () use ($data, $branchId, &$diubah) {
            foreach ($data['fisik'] as $partId => $fisik) {
                if ($fisik === null || $fisik === '') {
                    continue; // tidak dihitung = lewati, bukan dianggap nol
                }

                $part = Part::find($partId);
                if (! $part) {
                    continue;
                }

                $stokSistem = $part->stokDi($branchId);
                $selisih = (int) $fisik - $stokSistem;
                if ($selisih === 0) {
                    continue;
                }

                $part->moveStock($branchId, 'adjust', $selisih, [
                    'tipe' => 'opname',
                    'keterangan' => $data['keterangan'] ?: "Stok opname (sistem $stokSistem → fisik $fisik)",
                ]);
                $diubah++;
            }
        });

        if ($diubah) {
            \App\Models\ActivityLog::catat('stok_opname', "$diubah item disesuaikan");
        }

        return redirect()->route('stock.index')
            ->with($diubah ? 'success' : 'warning', $diubah
                ? "Opname selesai, $diubah item disesuaikan."
                : 'Tidak ada selisih stok yang perlu disesuaikan.');
    }

    /** Semua mutasi lintas part. */
    public function moves(Request $request)
    {
        $moves = StockMove::with('part', 'user')
            ->where('branch_id', current_branch())
            ->when($request->get('tipe'), fn ($q, $v) => $q->where('tipe', $v))
            ->when($request->get('dari'), fn ($q, $v) => $q->whereDate('tgl', '>=', $v))
            ->when($request->get('sampai'), fn ($q, $v) => $q->whereDate('tgl', '<=', $v))
            ->latest('tgl')->latest('id')->paginate(30)->withQueryString();

        return view('stock.moves', compact('moves'));
    }
}
