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
        $purchases = Purchase::with('supplier')
            ->when($request->get('q'), fn ($q, $v) => $q->where('no', 'like', "%$v%"))
            ->latest('tgl')->latest('id')->paginate(15)->withQueryString();

        return view('purchases.index', ['purchases' => $purchases, 'q' => $request->get('q')]);
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

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pembelian tersimpan, stok bertambah.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.part');

        return view('purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase)
    {
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

        return redirect()->route('purchases.index')->with('success', 'Pembelian dibatalkan, stok dikembalikan.');
    }

    private function nomorBaru(): string
    {
        return 'PB' . now()->format('ymd') . str_pad((string) (Purchase::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}
