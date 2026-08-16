<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PosController;
use App\Models\ActivityLog;
use App\Models\EditRequest;
use App\Models\Transaction;
use App\Support\TransactionEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $q = Transaction::query()->where('branch_id', current_branch())
            ->with('customer:id,nama', 'vehicle:id,plat');
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($tipe = $request->get('tipe')) {
            $q->where('tipe', $tipe);
        }
        if ($s = $request->get('search')) {
            $q->where('no_nota', 'like', "%$s%");
        }
        if ($d = $request->get('dari')) {
            $q->whereDate('tgl', '>=', $d);
        }
        if ($d = $request->get('sampai')) {
            $q->whereDate('tgl', '<=', $d);
        }

        return $q->latest('tgl')->latest('id')->limit(100)->get();
    }

    public function show(Transaction $transaction)
    {
        return $this->detail($transaction);
    }

    /** Buat transaksi POS. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipe' => 'required|in:penjualan,servis',
            'platform_id' => 'nullable|exists:platforms,id',
            'customer_id' => [Rule::requiredIf($request->tipe === 'servis'), 'nullable', 'exists:customers,id'],
            'vehicle_id' => [Rule::requiredIf($request->tipe === 'servis'), 'nullable', 'exists:vehicles,id'],
            'mekanik_id' => 'nullable|exists:users,id',
            'keluhan' => 'nullable|string',
            'status_servis' => 'nullable|in:antri,dikerjakan,selesai',
            'diskon' => 'nullable|numeric|min:0',
            'promo_id' => 'nullable|exists:promos,id',
            'payments' => 'nullable|array',
            'payments.*.metode' => 'required_with:payments|in:tunai,transfer,qris,kartu',
            'payments.*.jumlah' => 'required_with:payments|numeric|min:0',
            'metode' => 'nullable|in:tunai,transfer,qris,kartu',
            'bayar' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.tipe' => 'required|in:jasa,part',
            'items.*.ref_id' => 'nullable|integer',
            'items.*.nama' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
        ], [], ['items' => 'item transaksi']);

        $trx = PosController::createTransaction($data, current_branch(), $request->user()->id);
        ActivityLog::catat('transaksi_baru', "{$trx->no_nota} • " . rupiah($trx->total), 'transaction', $trx->id);

        return $this->detail($trx);
    }

    public function addPayment(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'metode' => 'required|in:tunai,transfer,qris,kartu',
        ]);
        if ($transaction->status === 'batal') {
            throw ValidationException::withMessages(['jumlah' => 'Transaksi sudah dibatalkan.']);
        }
        $sisa = $transaction->sisa;
        if ($sisa <= 0) {
            throw ValidationException::withMessages(['jumlah' => 'Transaksi sudah lunas.']);
        }

        $transaction->payments()->create([
            'branch_id' => $transaction->branch_id,
            'jumlah' => min($data['jumlah'], $sisa),
            'metode' => $data['metode'],
            'tgl_bayar' => now(),
        ]);
        if ($transaction->fresh()->sisa <= 0 && $transaction->status === 'selesai') {
            $transaction->update(['status' => 'lunas']);
        }

        return $this->detail($transaction->fresh());
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $data = $request->validate(['status' => 'required|in:antri,dikerjakan,selesai,lunas']);
        if ($transaction->status === 'batal') {
            throw ValidationException::withMessages(['status' => 'Transaksi sudah dibatalkan.']);
        }
        if ($data['status'] === 'lunas' && $transaction->sisa > 0) {
            throw ValidationException::withMessages(['status' => 'Belum lunas — sisa ' . rupiah($transaction->sisa) . '.']);
        }
        $transaction->update(['status' => $data['status']]);

        return $this->detail($transaction->fresh());
    }

    /** Edit item transaksi. Terbuka → langsung; terkunci & bukan admin → diajukan. */
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status === 'batal') {
            throw ValidationException::withMessages(['items' => 'Transaksi sudah dibatalkan.']);
        }
        $proposal = $transaction->terkunci() && ! $request->user()->canAccess('transactions_edit');

        $data = $request->validate([
            'platform_id' => 'nullable|exists:platforms,id',
            'customer_id' => 'nullable|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'mekanik_id' => 'nullable|exists:users,id',
            'keluhan' => 'nullable|string',
            'catatan_mekanik' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'tgl' => 'required|date',
            'alasan' => [Rule::requiredIf($proposal), 'nullable', 'string', 'max:255'],
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.tipe' => 'required|in:jasa,part',
            'items.*.ref_id' => 'nullable|integer',
            'items.*.nama' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
        ], [], ['items' => 'item transaksi']);

        if ($proposal) {
            EditRequest::create([
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id ?? current_branch(),
                'jenis' => 'edit',
                'payload' => Arr::except($data, 'alasan'),
                'alasan' => $data['alasan'],
                'user_id' => $request->user()->id,
            ]);
            ActivityLog::catat('ajukan_edit', "{$transaction->no_nota} — {$data['alasan']}", 'transaction', $transaction->id);

            return ['status' => 'pending', 'message' => 'Perubahan diajukan, menunggu persetujuan admin.'];
        }

        try {
            $perubahan = TransactionEditor::apply($transaction, $data);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }
        $ket = $perubahan ? implode(', ', $perubahan) : 'ubah data';
        ActivityLog::catat('edit_transaksi', mb_strimwidth("{$transaction->no_nota}: {$ket}", 0, 250, '…'), 'transaction', $transaction->id);

        return $this->detail($transaction->fresh());
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        if ($transaction->status === 'batal') {
            throw ValidationException::withMessages(['alasan_batal' => 'Transaksi sudah dibatalkan.']);
        }
        $data = $request->validate(['alasan_batal' => 'required|string|max:255']);

        if (! $request->user()->canAccess('transactions_edit')) {
            EditRequest::create([
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id ?? current_branch(),
                'jenis' => 'batal',
                'alasan' => $data['alasan_batal'],
                'user_id' => $request->user()->id,
            ]);
            ActivityLog::catat('ajukan_batal', "{$transaction->no_nota} — {$data['alasan_batal']}", 'transaction', $transaction->id);

            return ['status' => 'pending', 'message' => 'Pembatalan diajukan, menunggu persetujuan admin.'];
        }

        try {
            TransactionEditor::cancel($transaction, $data['alasan_batal']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['alasan_batal' => $e->getMessage()]);
        }
        ActivityLog::catat('batal_transaksi', "{$transaction->no_nota} — {$data['alasan_batal']}", 'transaction', $transaction->id);

        return $this->detail($transaction->fresh());
    }

    /** Bentuk detail transaksi yang dipakai mobile (item + pembayaran + relasi + dibayar/sisa). */
    private function detail(Transaction $transaction)
    {
        $transaction->load('items', 'payments', 'customer', 'vehicle', 'mekanik:id,name', 'kasir:id,name');
        $transaction->dibayar = $transaction->dibayar;
        $transaction->sisa = $transaction->sisa;

        return $transaction;
    }
}
