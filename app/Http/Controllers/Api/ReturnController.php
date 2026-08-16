<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Part;
use App\Models\SalesReturn;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnController extends Controller
{
    public function index()
    {
        return SalesReturn::with('transaction:id,no_nota', 'user:id,name')
            ->where('branch_id', current_branch())
            ->latest('tgl')->latest('id')->limit(100)->get();
    }

    /** Item yang masih bisa diretur dari sebuah transaksi. */
    public function options(Transaction $transaction)
    {
        abort_if($transaction->status === 'batal', 404);
        $transaction->load('items.returnItems');

        return [
            'transaction' => $transaction->only('id', 'no_nota', 'tipe'),
            'items' => $transaction->items
                ->filter(fn ($it) => $it->sisaRetur() > 0)
                ->map(fn ($it) => [
                    'tx_item_id' => $it->id,
                    'nama' => $it->nama,
                    'tipe' => $it->tipe,
                    'harga' => (float) $it->harga,
                    'qty' => $it->qty,
                    'diretur' => $it->qtyDiretur(),
                    'sisa' => $it->sisaRetur(),
                ])->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'alasan' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.tx_item_id' => 'required|exists:tx_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $trx = Transaction::with('items')->findOrFail($data['transaction_id']);
        if ($trx->status === 'batal') {
            throw ValidationException::withMessages(['transaction_id' => 'Transaksi sudah dibatalkan.']);
        }

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
                $qty = min((int) $row['qty'], $item->sisaRetur());
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

        return $this->show($retur);
    }

    public function show(SalesReturn $return)
    {
        return $return->load('items', 'transaction:id,no_nota', 'user:id,name');
    }
}
