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
        $this->pastikanBolehEdit($transaction);

        $data = $request->validate([
            'platform_id' => 'nullable|exists:platforms,id',
            'customer_id' => 'nullable|exists:customers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'mekanik_id' => 'nullable|exists:users,id',
            'keluhan' => 'nullable|string',
            'catatan_mekanik' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'tgl' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.tipe' => 'required|in:jasa,part',
            'items.*.ref_id' => 'nullable|integer',
            'items.*.nama' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
        ], [], ['items' => 'item transaksi']);

        $branchId = $transaction->branch_id ?? current_branch();
        $perubahan = [];

        try {
            DB::transaction(function () use ($transaction, $data, $branchId, &$perubahan) {
                $lama = $transaction->items()->get()->keyBy('id');
                $qtyLama = $this->qtyPerPart($lama);
                $dikirimId = collect($data['items'])->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();

                // update / tambah item
                foreach ($data['items'] as $row) {
                    $sub = $row['qty'] * $row['harga'] - ($row['diskon'] ?? 0);
                    $atr = ['tipe' => $row['tipe'], 'ref_id' => $row['ref_id'] ?? null, 'nama' => $row['nama'],
                        'qty' => $row['qty'], 'harga' => $row['harga'], 'diskon' => $row['diskon'] ?? 0, 'subtotal' => $sub];

                    if (! empty($row['id']) && $item = $lama->get((int) $row['id'])) {
                        $diretur = $item->qtyDiretur();
                        if ($diretur > 0 && $row['qty'] < $diretur) {
                            throw new \RuntimeException("{$item->nama} sudah diretur {$diretur}, qty tak boleh kurang dari itu.");
                        }
                        if ($item->qty != $row['qty']) {
                            $perubahan[] = "{$item->nama} {$item->qty}→{$row['qty']}";
                        } elseif ((float) $item->harga != (float) $row['harga'] || (float) $item->diskon != (float) ($row['diskon'] ?? 0)) {
                            $perubahan[] = "harga {$item->nama}";
                        }
                        $item->update($atr);
                    } else {
                        $transaction->items()->create($atr);
                        $perubahan[] = "+{$row['nama']}×{$row['qty']}";
                    }
                }

                // hapus item yang tak dikirim lagi
                foreach ($lama as $id => $item) {
                    if (! in_array($id, $dikirimId, true)) {
                        if ($item->returnItems()->exists()) {
                            throw new \RuntimeException("{$item->nama} sudah diretur, tak bisa dihapus.");
                        }
                        $item->delete();
                        $perubahan[] = "−{$item->nama}×{$item->qty}";
                    }
                }

                // sesuaikan stok = delta per part (in bila berkurang, out bila bertambah)
                $qtyBaru = $this->qtyPerPart($transaction->items()->get());
                foreach (array_unique(array_merge(array_keys($qtyLama), array_keys($qtyBaru))) as $ref) {
                    $delta = ($qtyBaru[$ref] ?? 0) - ($qtyLama[$ref] ?? 0);
                    if ($delta === 0) {
                        continue;
                    }
                    Part::find($ref)?->moveStock($branchId, $delta > 0 ? 'out' : 'in', abs($delta), [
                        'tipe' => 'transaction_edit', 'id' => $transaction->id, 'keterangan' => 'Edit ' . $transaction->no_nota,
                    ]);
                }

                // hitung ulang nominal
                $subtotal = (float) $transaction->items()->sum('subtotal');
                $diskonTotal = min($subtotal, (float) ($data['diskon'] ?? 0));
                $dpp = $subtotal - $diskonTotal;
                $pajakPersen = \App\Models\Setting::get('pajak_aktif', '0') === '1' ? (float) \App\Models\Setting::get('pajak_persen', '0') : 0;
                $pajak = round($dpp * $pajakPersen / 100, 2);
                $total = $dpp + $pajak;

                $transaction->fill([
                    'platform_id' => $data['platform_id'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'vehicle_id' => $data['vehicle_id'] ?? null,
                    'mekanik_id' => $data['mekanik_id'] ?? null,
                    'keluhan' => $data['keluhan'] ?? null,
                    'catatan_mekanik' => $data['catatan_mekanik'] ?? null,
                    'tgl' => $data['tgl'],
                    'subtotal' => $subtotal, 'diskon' => $diskonTotal, 'pajak' => $pajak, 'total' => $total,
                ]);
                // turunkan status bila jadi belum lunas
                if ($transaction->status === 'lunas' && $transaction->dibayar < $total) {
                    $transaction->status = 'selesai';
                }
                $transaction->save();
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ket = $perubahan ? implode(', ', $perubahan) : 'ubah data';
        \App\Models\ActivityLog::catat('edit_transaksi', mb_strimwidth("{$transaction->no_nota}: {$ket}", 0, 250, '…'), 'transaction', $transaction->id);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi diperbarui.');
    }

    /**
     * Skema "open ticket": transaksi masih terbuka boleh diedit kasir;
     * yang sudah lunas hanya admin/owner (izin transactions_edit).
     */
    private function pastikanBolehEdit(Transaction $transaction): void
    {
        abort_if(
            $transaction->terkunci() && ! auth()->user()->canAccess('transactions_edit'),
            403,
            'Transaksi sudah lunas — hanya admin/owner yang dapat mengubahnya.'
        );
    }

    /** ref_id => total qty, hanya item tipe part. */
    private function qtyPerPart($items): array
    {
        $map = [];
        foreach ($items as $it) {
            if ($it->tipe === 'part' && $it->ref_id) {
                $map[$it->ref_id] = ($map[$it->ref_id] ?? 0) + $it->qty;
            }
        }

        return $map;
    }

    public function cancel(Request $request, Transaction $transaction)
    {
        if ($transaction->status === 'batal') {
            return back()->with('warning', 'Transaksi sudah dibatalkan.');
        }
        $data = $request->validate(['alasan_batal' => 'required|string|max:255'], [], ['alasan_batal' => 'alasan pembatalan']);

        DB::transaction(function () use ($transaction, $data) {
            // kembalikan stok part yang keluar
            foreach ($transaction->items()->where('tipe', 'part')->whereNotNull('ref_id')->get() as $item) {
                $part = Part::find($item->ref_id);
                $part?->moveStock($transaction->branch_id ?? current_branch(), 'in', $item->qty, [
                    'tipe' => 'transaction_batal', 'id' => $transaction->id,
                    'keterangan' => 'Pembatalan ' . $transaction->no_nota,
                ]);
            }
            $transaction->update(['status' => 'batal', 'alasan_batal' => $data['alasan_batal']]);
        });
        \App\Models\ActivityLog::catat('batal_transaksi', "{$transaction->no_nota} — {$data['alasan_batal']}", 'transaction', $transaction->id);

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Transaksi dibatalkan, stok dikembalikan.');
    }
}
