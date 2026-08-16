<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    /** Item pembelian yang masih bisa diretur ke supplier. */
    public function options(Purchase $purchase)
    {
        $purchase->load('items.part:id,kode,nama', 'items.returnItems');

        return [
            'purchase' => $purchase->only('id', 'no'),
            'items' => $purchase->items
                ->filter(fn ($it) => $it->sisaRetur() > 0)
                ->map(fn ($it) => [
                    'purchase_item_id' => $it->id,
                    'nama' => $it->part?->nama ?? '—',
                    'harga_beli' => (float) $it->harga_beli,
                    'qty' => $it->qty,
                    'diretur' => $it->qtyDiretur(),
                    'sisa' => $it->sisaRetur(),
                ])->values(),
        ];
    }

    public function store(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'alasan' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);
        $purchase->load('items.part', 'items.returnItems');

        $retur = DB::transaction(function () use ($purchase, $data) {
            $retur = PurchaseReturn::create([
                'no' => PurchaseReturn::nomorBaru(),
                'branch_id' => $purchase->branch_id,
                'purchase_id' => $purchase->id,
                'alasan' => $data['alasan'],
                'total' => 0,
                'user_id' => auth()->id(),
                'tgl' => now(),
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $item = $purchase->items->firstWhere('id', $row['purchase_item_id']);
                if (! $item) {
                    continue;
                }
                $qty = min((int) $row['qty'], $item->sisaRetur());
                if ($qty <= 0) {
                    continue;
                }
                $sub = $qty * $item->harga_beli;
                $total += $sub;
                $retur->items()->create([
                    'purchase_item_id' => $item->id,
                    'part_id' => $item->part_id,
                    'nama' => $item->part?->nama ?? '—',
                    'qty' => $qty,
                    'harga_beli' => $item->harga_beli,
                    'subtotal' => $sub,
                ]);
                if ($item->part_id) {
                    Part::findOrFail($item->part_id)->moveStock($purchase->branch_id, 'out', $qty, [
                        'tipe' => 'purchase_retur', 'id' => $retur->id,
                        'keterangan' => 'Retur pembelian ' . $retur->no . ' (' . $purchase->no . ')',
                    ]);
                }
            }
            $retur->update(['total' => $total]);

            return $retur;
        });

        ActivityLog::catat('retur_pembelian', "{$retur->no} • {$purchase->no} • " . rupiah($retur->total), 'purchase', $purchase->id);

        return $this->show($retur);
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return $purchaseReturn->load('items', 'purchase:id,no', 'user:id,name');
    }
}
