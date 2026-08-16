<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with('supplier')->withCount('items')
            ->when($request->get('q'), fn ($q, $v) => $q->where('no', 'like', "%$v%"))
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('dari'), fn ($q, $v) => $q->whereDate('tgl', '>=', $v))
            ->when($request->get('sampai'), fn ($q, $v) => $q->whereDate('tgl', '<=', $v))
            ->latest('tgl')->latest('id')->paginate(15)->withQueryString();

        return view('purchases.index', ['purchases' => $purchases, 'filters' => $request->all()]);
    }

    public function create()
    {
        return view('purchases.form', [
            'suppliers' => Supplier::orderBy('nama')->get(),
            'parts' => Part::withStok()->orderBy('nama')->get(['id', 'kode', 'nama', 'satuan', 'harga_beli']),
            'noBaru' => $this->nomorBaru(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'tgl' => 'required|date',
            'status' => 'nullable|in:lunas,belum_lunas',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ], [], ['items' => 'item barang']);

        $branchId = current_branch();
        $purchase = DB::transaction(function () use ($data, $branchId) {
            $purchase = Purchase::create([
                'no' => $this->nomorBaru(),
                'branch_id' => $branchId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'tgl' => $data['tgl'],
                'status' => $data['status'] ?? 'lunas',
                'total' => 0,
                'user_id' => auth()->id(),
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $part = Part::findOrFail($row['part_id']);
                $subtotal = $row['qty'] * $row['harga_beli'];
                $total += $subtotal;

                $purchase->items()->create([
                    'part_id' => $part->id,
                    'qty' => $row['qty'],
                    'harga_beli' => $row['harga_beli'],
                    'subtotal' => $subtotal,
                ]);

                $part->moveStock($branchId, 'in', $row['qty'], [
                    'tipe' => 'purchase', 'id' => $purchase->id,
                    'keterangan' => 'Pembelian ' . $purchase->no,
                ]);

                // harga beli terakhir jadi acuan HPP berikutnya
                $part->update(['harga_beli' => $row['harga_beli']]);
            }

            $purchase->update(['total' => $total]);

            return $purchase;
        });

        \App\Models\ActivityLog::catat('pembelian', "{$purchase->no} • " . rupiah($purchase->total), 'purchase', $purchase->id);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian tersimpan, stok bertambah.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.part');

        return view('purchases.show', compact('purchase'));
    }

    /** Edit pembelian termasuk item barang. Izin purchases_edit. */
    public function edit(Purchase $purchase)
    {
        $purchase->load('items.part');

        return view('purchases.edit', [
            'purchase' => $purchase,
            'suppliers' => Supplier::orderBy('nama')->get(),
            'parts' => Part::withStok()->orderBy('nama')->get(['id', 'kode', 'nama', 'harga_beli']),
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'tgl' => 'required|date',
            'status' => 'required|in:lunas,belum_lunas',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.part_id' => 'required|exists:parts,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
        ], [], ['items' => 'item barang']);

        $branchId = $purchase->branch_id ?? current_branch();
        $perubahan = [];

        try {
            DB::transaction(function () use ($purchase, $data, $branchId, &$perubahan) {
                $lama = $purchase->items()->with('part')->get()->keyBy('id');
                $qtyLama = [];
                foreach ($lama as $it) {
                    $qtyLama[$it->part_id] = ($qtyLama[$it->part_id] ?? 0) + $it->qty;
                }
                $dikirimId = collect($data['items'])->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();

                $total = 0;
                foreach ($data['items'] as $row) {
                    $sub = $row['qty'] * $row['harga_beli'];
                    $total += $sub;
                    $part = Part::findOrFail($row['part_id']);
                    $atr = ['part_id' => $part->id, 'qty' => $row['qty'], 'harga_beli' => $row['harga_beli'], 'subtotal' => $sub];

                    if (! empty($row['id']) && $item = $lama->get((int) $row['id'])) {
                        if ($item->qty != $row['qty']) {
                            $perubahan[] = "{$part->nama} {$item->qty}→{$row['qty']}";
                        }
                        $item->update($atr);
                    } else {
                        $purchase->items()->create($atr);
                        $perubahan[] = "+{$part->nama}×{$row['qty']}";
                    }
                    $part->update(['harga_beli' => $row['harga_beli']]);
                }

                foreach ($lama as $id => $item) {
                    if (! in_array($id, $dikirimId, true)) {
                        $item->delete();
                        $perubahan[] = "−{$item->part?->nama}×{$item->qty}";
                    }
                }

                // delta stok: pembelian menambah stok, jadi qty naik = in, qty turun = out
                $qtyBaru = [];
                foreach ($purchase->items()->get() as $it) {
                    $qtyBaru[$it->part_id] = ($qtyBaru[$it->part_id] ?? 0) + $it->qty;
                }
                foreach (array_unique(array_merge(array_keys($qtyLama), array_keys($qtyBaru))) as $ref) {
                    $delta = ($qtyBaru[$ref] ?? 0) - ($qtyLama[$ref] ?? 0);
                    if ($delta === 0) {
                        continue;
                    }
                    Part::find($ref)?->moveStock($branchId, $delta > 0 ? 'in' : 'out', abs($delta), [
                        'tipe' => 'purchase_edit', 'id' => $purchase->id, 'keterangan' => 'Edit pembelian ' . $purchase->no,
                    ]);
                }

                $purchase->update(['supplier_id' => $data['supplier_id'] ?? null, 'tgl' => $data['tgl'], 'status' => $data['status'], 'total' => $total]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ket = $perubahan ? implode(', ', $perubahan) : 'ubah data';
        \App\Models\ActivityLog::catat('edit_pembelian', mb_strimwidth("{$purchase->no}: {$ket}", 0, 250, '…'), 'purchase', $purchase->id);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian diperbarui.');
    }

    public function markPaid(Purchase $purchase)
    {
        $purchase->update(['status' => 'lunas']);
        \App\Models\ActivityLog::catat('pembelian_lunas', $purchase->no, 'purchase', $purchase->id);

        return back()->with('success', 'Pembelian ditandai lunas.');
    }

    public function destroy(Request $request, Purchase $purchase)
    {
        $data = $request->validate(['alasan_batal' => 'required|string|max:255'], [], ['alasan_batal' => 'alasan pembatalan']);

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items()->with('part')->get() as $item) {
                // kembalikan stok; gagal (stok sudah terjual) → batalkan seluruh pembatalan
                $item->part->moveStock($purchase->branch_id, 'out', $item->qty, [
                    'tipe' => 'purchase_batal', 'id' => $purchase->id,
                    'keterangan' => 'Pembatalan pembelian ' . $purchase->no,
                ]);
            }
            $purchase->delete();
        });
        \App\Models\ActivityLog::catat('batal_pembelian', "{$purchase->no} — {$data['alasan_batal']}", 'purchase', $purchase->id);

        return redirect()->route('purchases.index')->with('success', 'Pembelian dibatalkan, stok dikembalikan.');
    }

    private function nomorBaru(): string
    {
        return 'PB' . now()->format('ymd') . str_pad((string) (Purchase::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}
