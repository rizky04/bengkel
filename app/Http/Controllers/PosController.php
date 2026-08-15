<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Platform;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public function create()
    {
        return view('pos.create', [
            'platforms' => Platform::where('aktif', true)->orderBy('nama')->get(),
            'customers' => Customer::orderBy('nama')->get(['id', 'nama', 'hp']),
            'vehicles' => Vehicle::get(['id', 'customer_id', 'plat', 'merk', 'tipe']),
            'mekaniks' => User::whereIn('role', ['mekanik', 'admin'])->where('aktif', true)->orderBy('name')->get(['id', 'name']),
            'parts' => Part::withStok()->orderBy('nama')->get(['id', 'kode', 'nama', 'harga_jual', 'satuan']),
            'services' => Service::orderBy('nama')->get(['id', 'nama', 'tarif']),
            'promos' => Promo::berlaku()->orderBy('nama')->get(),
            'pajakAktif' => (bool) Setting::get('pajak_aktif', '0'),
            'pajakPersen' => (float) Setting::get('pajak_persen', '0'),
        ]);
    }

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
            // Pembayaran bisa split (banyak metode). Format lama (metode+bayar) tetap didukung.
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

        $branchId = current_branch();
        $trx = DB::transaction(function () use ($data, $request, $branchId) {
            // 1) hitung subtotal dari item
            $subtotal = 0;
            foreach ($data['items'] as $it) {
                $subtotal += $it['qty'] * $it['harga'] - ($it['diskon'] ?? 0);
            }

            // 2) diskon manual + promo
            $diskonManual = $data['diskon'] ?? 0;
            $promoPotong = 0;
            if (! empty($data['promo_id'])) {
                $promo = Promo::berlaku()->find($data['promo_id']);
                if ($promo) {
                    $promoPotong = $promo->potongan($subtotal - $diskonManual);
                    $promo->increment('terpakai');
                }
            }
            $diskonTotal = min($subtotal, $diskonManual + $promoPotong);

            // 3) pajak & total
            $dpp = $subtotal - $diskonTotal;
            $pajakPersen = Setting::get('pajak_aktif', '0') === '1' ? (float) Setting::get('pajak_persen', '0') : 0;
            $pajak = round($dpp * $pajakPersen / 100, 2);
            $total = $dpp + $pajak;

            // 4) daftar pembayaran (split) + total dibayar
            $payments = collect($data['payments'] ?? [])
                ->map(fn ($p) => ['metode' => $p['metode'], 'jumlah' => (float) $p['jumlah']])
                ->filter(fn ($p) => $p['jumlah'] > 0)->values();
            if ($payments->isEmpty() && ($data['bayar'] ?? 0) > 0) { // format lama
                $payments = collect([['metode' => $data['metode'] ?? 'tunai', 'jumlah' => (float) $data['bayar']]]);
            }
            $bayar = $payments->sum('jumlah');

            // status
            if ($data['tipe'] === 'penjualan') {
                $status = $bayar >= $total ? 'lunas' : 'selesai';
            } else {
                $status = $data['status_servis'] ?? 'antri';
                if ($status === 'selesai' && $total > 0 && $bayar >= $total) {
                    $status = 'lunas';
                }
            }

            // 5) simpan transaksi
            $trx = Transaction::create([
                'no_nota' => Transaction::nomorBaru(),
                'branch_id' => $branchId,
                'tipe' => $data['tipe'],
                'platform_id' => $data['platform_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'mekanik_id' => $data['mekanik_id'] ?? null,
                'keluhan' => $data['keluhan'] ?? null,
                'status' => $status,
                'subtotal' => $subtotal,
                'diskon' => $diskonTotal,
                'promo_id' => $data['promo_id'] ?? null,
                'pajak' => $pajak,
                'total' => $total,
                'user_id' => auth()->id(),
                'tgl' => now(),
            ]);

            // 6) item + potong stok untuk part (lewat jalur tunggal moveStock)
            foreach ($data['items'] as $it) {
                $sub = $it['qty'] * $it['harga'] - ($it['diskon'] ?? 0);
                $trx->items()->create([
                    'tipe' => $it['tipe'],
                    'ref_id' => $it['ref_id'] ?? null,
                    'nama' => $it['nama'],
                    'qty' => $it['qty'],
                    'harga' => $it['harga'],
                    'diskon' => $it['diskon'] ?? 0,
                    'subtotal' => $sub,
                ]);

                if ($it['tipe'] === 'part' && ! empty($it['ref_id'])) {
                    Part::findOrFail($it['ref_id'])->moveStock($branchId, 'out', $it['qty'], [
                        'tipe' => 'transaction', 'id' => $trx->id,
                        'keterangan' => 'Penjualan ' . $trx->no_nota,
                    ]);
                }
            }

            // 7) pembayaran — dicatat per metode, total tercatat dibatasi = total transaksi
            //    (kelebihan tunai = kembalian, tidak disimpan sebagai kas masuk)
            $sisa = $total;
            foreach ($payments as $p) {
                $rec = min($p['jumlah'], $sisa);
                if ($rec <= 0) {
                    continue;
                }
                $trx->payments()->create(['jumlah' => $rec, 'metode' => $p['metode'], 'tgl_bayar' => now()]);
                $sisa -= $rec;
            }

            return $trx;
        });

        \App\Models\ActivityLog::catat('transaksi_baru', "{$trx->no_nota} • " . rupiah($trx->total), 'transaction', $trx->id);

        return redirect()->route('transactions.show', $trx)
            ->with('success', "Transaksi {$trx->no_nota} tersimpan.");
    }
}
