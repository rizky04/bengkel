<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $trx = Transaction::with('customer', 'vehicle', 'platform')
            ->when($request->get('q'), fn ($q, $v) => $q->where('no_nota', 'like', "%$v%"))
            ->when($request->get('tipe'), fn ($q, $v) => $q->where('tipe', $v))
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('dari'), fn ($q, $v) => $q->whereDate('tgl', '>=', $v))
            ->when($request->get('sampai'), fn ($q, $v) => $q->whereDate('tgl', '<=', $v))
            ->latest('tgl')->latest('id')->paginate(20)->withQueryString();

        return view('transactions.index', ['transactions' => $trx, 'filters' => $request->all()]);
    }

    public function export(Request $request)
    {
        $rows = Transaction::with('customer', 'vehicle', 'platform')
            ->when($request->get('tipe'), fn ($q, $v) => $q->where('tipe', $v))
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('dari'), fn ($q, $v) => $q->whereDate('tgl', '>=', $v))
            ->when($request->get('sampai'), fn ($q, $v) => $q->whereDate('tgl', '<=', $v))
            ->latest('tgl')->get()
            ->map(fn ($t) => [
                $t->no_nota, $t->tgl?->format('Y-m-d H:i'), $t->tipe, $t->platform?->nama,
                $t->customer?->nama, $t->vehicle?->plat, $t->status,
                (int) $t->subtotal, (int) $t->diskon, (int) $t->pajak, (int) $t->total, (int) $t->dibayar, (int) max(0, $t->sisa),
            ]);

        return csv_download('transaksi-' . now()->format('Ymd') . '.csv',
            ['no_nota', 'tanggal', 'tipe', 'platform', 'pelanggan', 'kendaraan', 'status', 'subtotal', 'diskon', 'pajak', 'total', 'dibayar', 'sisa'], $rows);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('items', 'payments', 'customer', 'vehicle', 'mekanik', 'kasir', 'platform', 'promo');

        return view('transactions.show', ['trx' => $transaction]);
    }

    public function nota(Transaction $transaction)
    {
        $transaction->load('items', 'payments', 'customer', 'vehicle', 'kasir');

        return view('transactions.nota', ['trx' => $transaction]);
    }

    public function addPayment(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'metode' => 'required|in:tunai,transfer,qris,kartu',
        ]);

        if ($transaction->status === 'batal') {
            return back()->with('error', 'Transaksi sudah dibatalkan.');
        }

        $sisa = $transaction->sisa;
        if ($sisa <= 0) {
            return back()->with('warning', 'Transaksi sudah lunas.');
        }

        $transaction->payments()->create([
            'jumlah' => min($data['jumlah'], $sisa), // kelebihan = kembalian
            'metode' => $data['metode'],
            'tgl_bayar' => now(),
        ]);

        // lunas otomatis kalau pekerjaan sudah selesai & pembayaran penuh
        if ($transaction->fresh()->sisa <= 0 && $transaction->status === 'selesai') {
            $transaction->update(['status' => 'lunas']);
        }

        return back()->with('success', 'Pembayaran dicatat.');
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'status' => 'required|in:antri,dikerjakan,selesai,lunas',
        ]);

        if ($transaction->status === 'batal') {
            return back()->with('error', 'Transaksi sudah dibatalkan.');
        }

        // hanya boleh 'lunas' bila benar-benar lunas
        if ($data['status'] === 'lunas' && $transaction->sisa > 0) {
            return back()->with('error', 'Belum lunas — sisa ' . rupiah($transaction->sisa) . '.');
        }

        $transaction->update(['status' => $data['status']]);

        return back()->with('success', 'Status diperbarui: ' . $data['status'] . '.');
    }

    public function cancel(Transaction $transaction)
    {
        if ($transaction->status === 'batal') {
            return back()->with('warning', 'Transaksi sudah dibatalkan.');
        }

        DB::transaction(function () use ($transaction) {
            // kembalikan stok part yang keluar
            foreach ($transaction->items()->where('tipe', 'part')->whereNotNull('ref_id')->get() as $item) {
                $part = Part::find($item->ref_id);
                $part?->moveStock($transaction->branch_id ?? current_branch(), 'in', $item->qty, [
                    'tipe' => 'transaction_batal', 'id' => $transaction->id,
                    'keterangan' => 'Pembatalan ' . $transaction->no_nota,
                ]);
            }
            $transaction->update(['status' => 'batal']);
        });
        \App\Models\ActivityLog::catat('batal_transaksi', $transaction->no_nota, 'transaction', $transaction->id);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi dibatalkan, stok dikembalikan.');
    }
}
