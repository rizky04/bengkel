<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Part;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $parts = Part::with('category')->withStok()
            ->when($q, fn ($query) => $query
                ->where('nama', 'like', "%$q%")->orWhere('kode', 'like', "%$q%"))
            ->when($request->get('low'), fn ($query) => $query->lowStock())
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('parts.index', compact('parts', 'q'));
    }

    public function export()
    {
        $rows = Part::with('category')->orderBy('nama')->get()->map(fn ($p) => [
            $p->kode, $p->nama, $p->category?->nama, $p->satuan,
            (int) $p->harga_beli, (int) $p->harga_jual, $p->stok, $p->stok_min, $p->lokasi_rak,
        ]);

        return csv_download('sparepart-' . now()->format('Ymd') . '.csv',
            ['kode', 'nama', 'kategori', 'satuan', 'harga_beli', 'harga_jual', 'stok', 'stok_min', 'lokasi_rak'], $rows);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle); // lewati baris judul
        $baru = 0;
        $update = 0;

        DB::transaction(function () use ($handle, &$baru, &$update) {
            while (($r = fgetcsv($handle)) !== false) {
                [$kode, $nama] = [trim($r[0] ?? ''), trim($r[1] ?? '')];
                if ($kode === '' || $nama === '') {
                    continue;
                }
                $kategori = ! empty($r[2]) ? Category::firstOrCreate(['nama' => trim($r[2]), 'tipe' => 'part'])->id : null;
                $data = [
                    'nama' => $nama, 'category_id' => $kategori, 'satuan' => trim($r[3] ?? 'pcs') ?: 'pcs',
                    'harga_beli' => (float) ($r[4] ?? 0), 'harga_jual' => (float) ($r[5] ?? 0),
                    'stok_min' => (int) ($r[7] ?? 0), 'lokasi_rak' => trim($r[8] ?? '') ?: null,
                ];

                $part = Part::where('kode', $kode)->first();
                if ($part) {
                    $part->update($data); // stok existing tak diubah via import (pakai opname)
                    $update++;
                } else {
                    $stokAwal = (int) ($r[6] ?? 0);
                    $part = Part::create([...$data, 'kode' => $kode]);
                    if ($stokAwal > 0) {
                        $part->moveStock(current_branch(), 'in', $stokAwal, ['tipe' => 'import', 'keterangan' => 'Stok awal (import)']);
                    }
                    $baru++;
                }
            }
        });
        fclose($handle);

        return back()->with('success', "Import selesai: $baru barang baru, $update diperbarui.");
    }

    public function label(Request $request, Part $part)
    {
        $qty = max(1, min(60, (int) $request->get('qty', 12))); // batasi 1..60 label

        return view('parts.label', compact('part', 'qty'));
    }

    public function create()
    {
        $kode = 'SP' . str_pad((int) Part::max('id') + 1, 4, '0', STR_PAD_LEFT);

        return view('parts.form', $this->formData(new Part(['kode' => $kode, 'satuan' => 'pcs'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $stokAwal = (int) $data['stok'];
        unset($data['stok']); // stok kini di inventories per cabang

        DB::transaction(function () use ($data, $stokAwal) {
            $part = Part::create($data);
            if ($stokAwal > 0) {
                $part->moveStock(current_branch(), 'in', $stokAwal, ['tipe' => 'stok_awal', 'keterangan' => 'Stok awal']);
            }
        });

        return redirect()->route('parts.index')->with('success', 'Sparepart ditambahkan.');
    }

    public function edit(Part $part)
    {
        return view('parts.form', $this->formData($part));
    }

    public function update(Request $request, Part $part)
    {
        // stok tidak diubah lewat sini (pakai pembelian/opname); abaikan field stok
        $data = $this->validated($request, $part->id);
        unset($data['stok']);
        $part->update($data);

        return redirect()->route('parts.index')->with('success', 'Sparepart diperbarui.');
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return back()->with('success', 'Sparepart dihapus.');
    }

    private function formData(Part $part): array
    {
        return [
            'part' => $part,
            'categories' => Category::where('tipe', 'part')->orderBy('nama')->get(),
            'suppliers' => Supplier::orderBy('nama')->get(),
        ];
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
