<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Part;
use App\Models\StockMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $branchId = current_branch();
        $q = Part::with('category:id,nama')->withStok($branchId);
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('kode', 'like', "%$s%"));
        }
        if ($request->boolean('low')) {
            $q->lowStock($branchId);
        }
        $parts = $q->orderBy('nama')->limit(300)->get();

        $nilaiPersediaan = (float) Inventory::where('branch_id', $branchId)
            ->join('parts', 'parts.id', '=', 'inventories.part_id')
            ->sum(DB::raw('inventories.stok * parts.harga_beli'));

        return [
            'parts' => $parts,
            'nilai_persediaan' => $nilaiPersediaan,
            'jumlah_menipis' => Part::lowStock($branchId)->count(),
        ];
    }

    /** Semua mutasi stok cabang (opsional per part). */
    public function moves(Request $request)
    {
        $q = StockMove::with('part:id,nama,kode', 'user:id,name')
            ->where('branch_id', current_branch());
        if ($partId = $request->get('part_id')) {
            $q->where('part_id', $partId);
        }
        if ($tipe = $request->get('tipe')) {
            $q->where('tipe', $tipe);
        }

        return $q->latest('tgl')->latest('id')->limit(200)->get();
    }

    public function opname(Request $request)
    {
        $data = $request->validate([
            'fisik' => 'required|array',
            'fisik.*' => 'nullable|integer|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $branchId = current_branch();
        $diubah = 0;
        DB::transaction(function () use ($data, $branchId, &$diubah) {
            foreach ($data['fisik'] as $partId => $fisik) {
                if ($fisik === null || $fisik === '') {
                    continue;
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
            ActivityLog::catat('stok_opname', "$diubah item disesuaikan");
        }

        return ['ok' => true, 'diubah' => $diubah];
    }

    public function transferOptions()
    {
        return [
            'branches' => Branch::aktif()->where('id', '!=', current_branch())->orderBy('nama')->get(['id', 'nama']),
            'parts' => Part::withStok()->orderBy('nama')->get(['id', 'kode', 'nama', 'satuan']),
        ];
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'part_id' => 'required|exists:parts,id',
            'ke_branch_id' => 'required|exists:branches,id',
            'qty' => 'required|integer|min:1',
        ]);
        $dari = current_branch();
        if ((int) $data['ke_branch_id'] === (int) $dari) {
            throw ValidationException::withMessages(['ke_branch_id' => 'Cabang tujuan harus berbeda.']);
        }
        $part = Part::findOrFail($data['part_id']);
        try {
            DB::transaction(function () use ($part, $dari, $data) {
                $part->moveStock($dari, 'out', $data['qty'], ['tipe' => 'transfer', 'keterangan' => 'Transfer ke cabang #' . $data['ke_branch_id']]);
                $part->moveStock((int) $data['ke_branch_id'], 'in', $data['qty'], ['tipe' => 'transfer', 'keterangan' => 'Transfer dari cabang #' . $dari]);
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['qty' => $e->getMessage()]);
        }

        return ['ok' => true];
    }
}
