<?php

namespace App\Support;

use App\Models\Part;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Satu jalur penerapan perubahan transaksi — dipakai baik saat admin/kasir
 * mengubah langsung, maupun saat admin menyetujui pengajuan kasir.
 * Semua penyesuaian stok lewat Part::moveStock (atomik, tak boleh minus).
 */
class TransactionEditor
{
    /**
     * Terapkan usulan item + metadata ke transaksi. Menghitung ulang nominal,
     * menyesuaikan stok per selisih, dan menurunkan status bila jadi belum lunas.
     *
     * @param  array  $data  { platform_id, customer_id, vehicle_id, mekanik_id, keluhan, catatan_mekanik, diskon, tgl, items[] }
     * @return array daftar ringkasan perubahan (untuk log)
     */
    public static function apply(Transaction $transaction, array $data): array
    {
        $branchId = $transaction->branch_id ?? current_branch();
        $perubahan = [];

        DB::transaction(function () use ($transaction, $data, $branchId, &$perubahan) {
            $lama = $transaction->items()->get()->keyBy('id');
            $qtyLama = self::qtyPerPart($lama);
            $dikirimId = collect($data['items'])->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();

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

            foreach ($lama as $id => $item) {
                if (! in_array($id, $dikirimId, true)) {
                    if ($item->returnItems()->exists()) {
                        throw new \RuntimeException("{$item->nama} sudah diretur, tak bisa dihapus.");
                    }
                    $item->delete();
                    $perubahan[] = "−{$item->nama}×{$item->qty}";
                }
            }

            $qtyBaru = self::qtyPerPart($transaction->items()->get());
            foreach (array_unique(array_merge(array_keys($qtyLama), array_keys($qtyBaru))) as $ref) {
                $delta = ($qtyBaru[$ref] ?? 0) - ($qtyLama[$ref] ?? 0);
                if ($delta === 0) {
                    continue;
                }
                Part::find($ref)?->moveStock($branchId, $delta > 0 ? 'out' : 'in', abs($delta), [
                    'tipe' => 'transaction_edit', 'id' => $transaction->id, 'keterangan' => 'Edit ' . $transaction->no_nota,
                ]);
            }

            $subtotal = (float) $transaction->items()->sum('subtotal');
            $diskonTotal = min($subtotal, (float) ($data['diskon'] ?? 0));
            $dpp = $subtotal - $diskonTotal;
            $pajakPersen = Setting::get('pajak_aktif', '0') === '1' ? (float) Setting::get('pajak_persen', '0') : 0;
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
            if ($transaction->status === 'lunas' && $transaction->dibayar < $total) {
                $transaction->status = 'selesai';
            }
            $transaction->save();
        });

        return $perubahan;
    }

    /** Batalkan transaksi: kembalikan stok part & tandai batal + alasan. */
    public static function cancel(Transaction $transaction, string $alasan): void
    {
        DB::transaction(function () use ($transaction, $alasan) {
            foreach ($transaction->items()->where('tipe', 'part')->whereNotNull('ref_id')->get() as $item) {
                Part::find($item->ref_id)?->moveStock($transaction->branch_id ?? current_branch(), 'in', $item->qty, [
                    'tipe' => 'transaction_batal', 'id' => $transaction->id,
                    'keterangan' => 'Pembatalan ' . $transaction->no_nota,
                ]);
            }
            $transaction->update(['status' => 'batal', 'alasan_batal' => $alasan]);
        });
    }

    private static function qtyPerPart($items): array
    {
        $map = [];
        foreach ($items as $it) {
            if ($it->tipe === 'part' && $it->ref_id) {
                $map[$it->ref_id] = ($map[$it->ref_id] ?? 0) + $it->qty;
            }
        }

        return $map;
    }
}
