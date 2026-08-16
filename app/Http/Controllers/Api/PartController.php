<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $q = Part::withStok()->with('category:id,nama');
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('kode', 'like', "%$s%"));
        }
        if ($request->boolean('low')) {
            $q->lowStock();
        }

        return $q->orderBy('nama')->limit(300)->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $stokAwal = (int) $data['stok'];
        unset($data['stok']);

        $part = DB::transaction(function () use ($data, $stokAwal) {
            $part = Part::create($data);
            if ($stokAwal > 0) {
                $part->moveStock(current_branch(), 'in', $stokAwal, ['tipe' => 'stok_awal', 'keterangan' => 'Stok awal']);
            }

            return $part;
        });

        return Part::withStok()->find($part->id);
    }

    public function update(Request $request, Part $part)
    {
        $data = $this->validated($request, $part->id);
        unset($data['stok']); // stok lewat pembelian/opname
        $part->update($data);

        return Part::withStok()->find($part->id);
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return ['ok' => true];
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'kode' => 'required|string|max:50|unique:parts,kode' . ($ignore ? ",$ignore" : ''),
            'nama' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'satuan' => 'required|string|max:20',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_min' => 'required|integer|min:0',
            'lokasi_rak' => 'nullable|string|max:50',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);
    }
}
