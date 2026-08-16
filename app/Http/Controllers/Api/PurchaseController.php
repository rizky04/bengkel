<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $q = Purchase::with('supplier:id,nama')->withCount('items')
            ->where('branch_id', current_branch());
        if ($s = $request->get('search')) {
            $q->where('no', 'like', "%$s%");
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        return $q->latest('tgl')->latest('id')->limit(100)->get();
    }

    public function show(Purchase $purchase)
    {
        return $purchase->load('supplier:id,nama', 'items.part:id,kode,nama', 'items.returnItems', 'returns.user:id,name');
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
        ]);

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
                $sub = $row['qty'] * $row['harga_beli'];
                $total += $sub;
                $purchase->items()->create(['part_id' => $part->id, 'qty' => $row['qty'], 'harga_beli' => $row['harga_beli'], 'subtotal' => $sub]);
                $part->moveStock($branchId, 'in', $row['qty'], ['tipe' => 'purchase', 'id' => $purchase->id, 'keterangan' => 'Pembelian ' . $purchase->no]);
                $part->update(['harga_beli' => $row['harga_beli']]);
            }
            $purchase->update(['total' => $total]);

            return $purchase;
        });

        ActivityLog::catat('pembelian', "{$purchase->no} • " . rupiah($purchase->total), 'purchase', $purchase->id);

        return $this->show($purchase);
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
        ]);

        $branchId = $purchase->branch_id ?? current_branch();
        try {
            DB::transaction(function () use ($purchase, $data, $branchId) {
                $lama = $purchase->items()->get()->keyBy('id');
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
                        $item->update($atr);
                    } else {
                        $purchase->items()->create($atr);
                    }
                    $part->update(['harga_beli' => $row['harga_beli']]);
                }
                foreach ($lama as $id => $item) {
                    if (! in_array($id, $dikirimId, true)) {
                        $item->delete();
                    }
                }

                $qtyBaru = [];
                foreach ($purchase->items()->get() as $it) {
                    $qtyBaru[$it->part_id] = ($qtyBaru[$it->part_id] ?? 0) + $it->qty;
                }
                foreach (array_unique(array_merge(array_keys($qtyLama), array_keys($qtyBaru))) as $ref) {
                    $delta = ($qtyBaru[$ref] ?? 0) - ($qtyLama[$ref] ?? 0);
                    if ($delta === 0) {
                        continue;
                    }
                    Part::find($ref)?->moveStock($branchId, $delta > 0 ? 'in' : 'out', abs($delta), ['tipe' => 'purchase_edit', 'id' => $purchase->id, 'keterangan' => 'Edit pembelian ' . $purchase->no]);
                }
                $purchase->update(['supplier_id' => $data['supplier_id'] ?? null, 'tgl' => $data['tgl'], 'status' => $data['status'], 'total' => $total]);
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        ActivityLog::catat('edit_pembelian', $purchase->no, 'purchase', $purchase->id);

        return $this->show($purchase->fresh());
    }

    public function markPaid(Purchase $purchase)
    {
        $purchase->update(['status' => 'lunas']);
        ActivityLog::catat('pembelian_lunas', $purchase->no, 'purchase', $purchase->id);

        return $this->show($purchase);
    }

    public function destroy(Request $request, Purchase $purchase)
    {
        $data = $request->validate(['alasan_batal' => 'required|string|max:255']);
        try {
            DB::transaction(function () use ($purchase) {
                foreach ($purchase->items()->with('part')->get() as $item) {
                    $item->part->moveStock($purchase->branch_id, 'out', $item->qty, ['tipe' => 'purchase_batal', 'id' => $purchase->id, 'keterangan' => 'Pembatalan pembelian ' . $purchase->no]);
                }
                $purchase->delete();
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['alasan_batal' => $e->getMessage()]);
        }
        ActivityLog::catat('batal_pembelian', "{$purchase->no} — {$data['alasan_batal']}", 'purchase', $purchase->id);

        return ['ok' => true];
    }

    private function nomorBaru(): string
    {
        return 'PB' . now()->format('ymd') . str_pad((string) (Purchase::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT);
    }
}
