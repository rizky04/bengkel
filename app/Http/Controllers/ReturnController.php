<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\SalesReturn;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index()
    {
        $returns = SalesReturn::with('transaction.customer', 'user')
            ->where('branch_id', current_branch())
            ->latest('tgl')->latest('id')->paginate(20);

        return view('returns.index', compact('returns'));
    }

    /** Form retur untuk satu transaksi (dipilih dari daftar transaksi). */
    public function create(Request $request)
    {
        $transaction = Transaction::with('items', 'customer', 'vehicle')
            ->where('branch_id', current_branch())
            ->findOrFail($request->get('transaction'));

        abort_if($transaction->status === 'batal', 404);

        // hanya item yang masih bisa diretur
        $items = $transaction->items->filter(fn ($it) => $it->sisaRetur() > 0);
        abort_if($items->isEmpty(), 404, 'Tidak ada item yang bisa diretur.');

        return view('returns.create', compact('transaction', 'items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'alasan' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.tx_item_id' => 'required|exists:tx_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ], [], ['items' => 'item retur']);

        $trx = Transaction::with('items')->findOrFail($data['transaction_id']);
        abort_if($trx->status === 'batal', 422);

        $retur = DB::transaction(function () use ($trx, $data) {
            $retur = SalesReturn::create([
                'no' => SalesReturn::nomorBaru(),
                'branch_id' => $trx->branch_id,
                'transaction_id' => $trx->id,
                'alasan' => $data['alasan'],
                'total' => 0,
                'user_id' => auth()->id(),
                'tgl' => now(),
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $item = $trx->items->firstWhere('id', $row['tx_item_id']);
                if (! $item) {
                    continue;
                }
                $qty = min((int) $row['qty'], $item->sisaRetur()); // tak boleh lebih dari sisa
                if ($qty <= 0) {
                    continue;
                }
                $sub = $qty * $item->harga;
                $total += $sub;

                $retur->items()->create([
                    'tx_item_id' => $item->id,
                    'part_id' => $item->tipe === 'part' ? $item->ref_id : null,
                    'nama' => $item->nama,
                    'qty' => $qty,
                    'harga' => $item->harga,
                    'subtotal' => $sub,
                ]);

                // restock hanya untuk part (jasa tidak menambah stok)
                if ($item->tipe === 'part' && $item->ref_id) {
                    Part::findOrFail($item->ref_id)->moveStock($trx->branch_id, 'in', $qty, [
                        'tipe' => 'retur', 'id' => $retur->id,
                        'keterangan' => 'Retur ' . $retur->no . ' (' . $trx->no_nota . ')',
                    ]);
                }
            }

            $retur->update(['total' => $total]);

            return $retur;
        });

        ActivityLog::catat('retur', "{$retur->no} • {$trx->no_nota} • " . rupiah($retur->total), 'transaction', $trx->id);

        return redirect()->route('returns.show', $retur)->with('success', "Retur {$retur->no} tersimpan, stok dikembalikan.");
    }

    public function show(SalesReturn $return)
    {
        $return->load('items', 'transaction.customer', 'transaction.vehicle', 'user');

        return view('returns.show', ['retur' => $return]);
    }
}
