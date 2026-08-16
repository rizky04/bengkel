<?php

namespace App\Http\Controllers;

use App\Models\EditRequest;
use App\Models\Part;
use App\Models\Transaction;
use App\Support\TransactionEditor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $trx = Transaction::with('customer', 'vehicle', 'platform')
            ->where('branch_id', current_branch())
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
        $transaction->load('items.returnItems', 'payments', 'customer', 'vehicle', 'mekanik', 'kasir', 'platform', 'promo', 'returns');
        $pending = $transaction->pengajuanPending()->with('pengaju')->latest('id')->first();

        return view('transactions.show', ['trx' => $transaction, 'pending' => $pending]);
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
            'branch_id' => $transaction->branch_id,
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

    /**
     * Edit transaksi termasuk item (mis. tambah sparepart saat servis berjalan).
     * Terbuka → kasir langsung. Terkunci (lunas) → kasir mengajukan, admin menyetujui.
     */
    public function edit(Transaction $transaction)
    {
        abort_if($transaction->status === 'batal', 404);
        $transaction->load('items');

        return view('transactions.edit', [
            'trx' => $transaction,
            'modeAjuan' => $this->perluApproval($transaction),
            'pendingAda' => $transaction->pengajuanPending()->exists(),
            'customers' => \App\Models\Customer::orderBy('nama')->get(['id', 'nama', 'hp']),
            'vehicles' => \App\Models\Vehicle::get(['id', 'customer_id', 'plat', 'merk', 'tipe']),
            'mekaniks' => \App\Models\User::whereIn('role', ['mekanik', 'admin'])->where('aktif', true)->orderBy('name')->get(['id', 'name']),
            'platforms' => \App\Models\Platform::where('aktif', true)->orderBy('nama')->get(),
            'parts' => Part::withStok()->orderBy('nama')->get(['id', 'kode', 'nama', 'harga_jual']),
            'services' => \App\Models\Service::orderBy('nama')->get(['id', 'nama', 'tarif']),
            'pajakPersen' => \App\Models\Setting::get('pajak_aktif', '0') === '1' ? (float) \App\Models\Setting::get('pajak_persen', '0') : 0,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        abort_if($transaction->status === 'batal', 404);
        $proposal = $this->perluApproval($transaction);

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
        ], [], ['items' => 'item transaksi', 'alasan' => 'alasan perubahan']);

        // Terkunci & bukan admin → ajukan, jangan terapkan.
        if ($proposal) {
            EditRequest::create([
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id ?? current_branch(),
                'jenis' => 'edit',
                'payload' => Arr::except($data, 'alasan'),
                'alasan' => $data['alasan'],
                'user_id' => auth()->id(),
            ]);
            \App\Models\ActivityLog::catat('ajukan_edit', "{$transaction->no_nota} — {$data['alasan']}", 'transaction', $transaction->id);

            return redirect()->route('transactions.show', $transaction)->with('success', 'Perubahan diajukan, menunggu persetujuan admin.');
        }

        try {
            $perubahan = TransactionEditor::apply($transaction, $data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ket = $perubahan ? implode(', ', $perubahan) : 'ubah data';
        \App\Models\ActivityLog::catat('edit_transaksi', mb_strimwidth("{$transaction->no_nota}: {$ket}", 0, 250, '…'), 'transaction', $transaction->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi diperbarui.');
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        if ($transaction->status === 'batal') {
            return back()->with('warning', 'Transaksi sudah dibatalkan.');
        }
        $data = $request->validate(['alasan_batal' => 'required|string|max:255'], [], ['alasan_batal' => 'alasan pembatalan']);

        // Kasir → ajukan pembatalan; admin/owner → batalkan langsung.
        if (! auth()->user()->canAccess('transactions_edit')) {
            EditRequest::create([
                'transaction_id' => $transaction->id,
                'branch_id' => $transaction->branch_id ?? current_branch(),
                'jenis' => 'batal',
                'alasan' => $data['alasan_batal'],
                'user_id' => auth()->id(),
            ]);
            \App\Models\ActivityLog::catat('ajukan_batal', "{$transaction->no_nota} — {$data['alasan_batal']}", 'transaction', $transaction->id);

            return redirect()->route('transactions.show', $transaction)->with('success', 'Pembatalan diajukan, menunggu persetujuan admin.');
        }

        try {
            TransactionEditor::cancel($transaction, $data['alasan_batal']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        \App\Models\ActivityLog::catat('batal_transaksi', "{$transaction->no_nota} — {$data['alasan_batal']}", 'transaction', $transaction->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi dibatalkan, stok dikembalikan.');
    }

    /** Perlu approval bila transaksi terkunci (lunas) & pengguna bukan admin/owner. */
    private function perluApproval(Transaction $transaction): bool
    {
        return $transaction->terkunci() && ! auth()->user()->canAccess('transactions_edit');
    }
}
