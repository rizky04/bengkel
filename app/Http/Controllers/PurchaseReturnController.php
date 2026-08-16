<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function create(Purchase $purchase)
    {
        $purchase->load('items.part', 'items.returnItems', 'supplier');

        $items = $purchase->items->filter(fn ($it) => $it->sisaRetur() > 0);
        abort_if($items->isEmpty(), 404, 'Tidak ada item yang bisa diretur.');

        return view('purchases.returns.create', compact('purchase', 'items'));
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

                // barang dikembalikan ke supplier → stok berkurang
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

        return redirect()->route('purchase-returns.show', $retur)
            ->with('success', "Retur {$retur->no} tersimpan, stok dikurangi.");
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load('items', 'purchase.supplier', 'user');

        return view('purchases.returns.show', ['retur' => $purchaseReturn]);
    }
}
