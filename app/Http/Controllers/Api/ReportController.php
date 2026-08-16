<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\ReturnItem;
use App\Models\SalesReturn;
use App\Models\Transaction;
use App\Models\TxItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function range(Request $request): array
    {
        return [
            $request->get('dari', Carbon::now()->startOfMonth()->toDateString()),
            $request->get('sampai', Carbon::now()->toDateString()),
        ];
    }

    private function win(string $dari, string $sampai): array
    {
        return [$dari . ' 00:00:00', $sampai . ' 23:59:59'];
    }

    private function hpp(string $dari, string $sampai): float
    {
        return (float) TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->join('parts', 'parts.id', '=', 'tx_items.ref_id')
            ->where('transactions.branch_id', current_branch())
            ->where('transactions.status', '!=', 'batal')
            ->whereBetween('transactions.tgl', $this->win($dari, $sampai))
            ->sum(DB::raw('tx_items.qty * parts.harga_beli'));
    }

    private function retur(string $dari, string $sampai): array
    {
        $b = current_branch();
        $win = $this->win($dari, $sampai);
        $total = (float) SalesReturn::where('branch_id', $b)->whereBetween('tgl', $win)->sum('total');
        $hpp = (float) ReturnItem::whereNotNull('part_id')
            ->join('returns', 'returns.id', '=', 'return_items.return_id')
            ->join('parts', 'parts.id', '=', 'return_items.part_id')
            ->where('returns.branch_id', $b)->whereBetween('returns.tgl', $win)
            ->sum(DB::raw('return_items.qty * parts.harga_beli'));

        return ['total' => $total, 'hpp' => $hpp];
    }

    public function labaRugi(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();
        $bruto = (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $this->win($dari, $sampai))->sum('total');
        $retur = $this->retur($dari, $sampai);
        $pendapatan = $bruto - $retur['total'];
        $hpp = $this->hpp($dari, $sampai) - $retur['hpp'];
        $labaKotor = $pendapatan - $hpp;
        $totalPengeluaran = (float) Expense::where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)->sum('nominal');

        return compact('dari', 'sampai', 'bruto', 'pendapatan', 'hpp', 'labaKotor', 'totalPengeluaran')
            + ['retur' => $retur['total'], 'labaBersih' => $labaKotor - $totalPengeluaran];
    }

    public function arusKas(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();
        $win = $this->win($dari, $sampai);
        $kasMasuk = (float) Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)->sum('jumlah');
        $pengeluaran = (float) Expense::where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)->sum('nominal');
        $pembelian = (float) Purchase::where('branch_id', $b)->whereDate('tgl', '>=', $dari)->whereDate('tgl', '<=', $sampai)->sum('total');
        $refundRetur = $this->retur($dari, $sampai)['total'];

        return compact('dari', 'sampai', 'kasMasuk', 'pengeluaran', 'pembelian', 'refundRetur')
            + ['kasKeluar' => $pengeluaran + $pembelian + $refundRetur, 'perMetode' => Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->pluck('total', 'metode')];
    }

    public function penjualan(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();
        $win = $this->win($dari, $sampai);

        return [
            'dari' => $dari, 'sampai' => $sampai,
            'perPlatform' => Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)
                ->selectRaw('platform_id, COUNT(*) as jml, SUM(total) as total')->groupBy('platform_id')->with('platform:id,nama')->get(),
            'perTipe' => Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)
                ->selectRaw('tipe, COUNT(*) as jml, SUM(total) as total')->groupBy('tipe')->get(),
            'perMetode' => Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)
                ->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->get(),
            'totalOmzet' => (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('total'),
            'totalDiskon' => (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('diskon'),
        ];
    }

    public function stok(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();

        return [
            'dari' => $dari, 'sampai' => $sampai,
            'terlaris' => TxItem::where('tx_items.tipe', 'part')
                ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
                ->where('transactions.branch_id', $b)->where('transactions.status', '!=', 'batal')
                ->whereBetween('transactions.tgl', $this->win($dari, $sampai))
                ->selectRaw('tx_items.nama, SUM(tx_items.qty) as qty, SUM(tx_items.subtotal) as total')
                ->groupBy('tx_items.nama')->orderByDesc('qty')->limit(20)->get(),
            'nilaiPersediaan' => (float) Inventory::where('branch_id', $b)->join('parts', 'parts.id', '=', 'inventories.part_id')->sum(DB::raw('inventories.stok * parts.harga_beli')),
            'menipis' => Part::withStok($b)->lowStock($b)->orderBy('nama')->get(['id', 'kode', 'nama', 'stok_min']),
        ];
    }

    public function piutang()
    {
        $piutang = Transaction::aktif()->where('transactions.branch_id', current_branch())
            ->leftJoin('payments', 'payments.transaction_id', '=', 'transactions.id')
            ->selectRaw('transactions.*, COALESCE(SUM(payments.jumlah),0) as dibayar_sum')
            ->groupBy('transactions.id')
            ->havingRaw('transactions.total - COALESCE(SUM(payments.jumlah),0) > 0')
            ->with('customer:id,nama', 'vehicle:id,plat')
            ->orderBy('transactions.tgl')->get();

        return [
            'data' => $piutang->map(fn ($t) => [
                'id' => $t->id, 'no_nota' => $t->no_nota, 'tgl' => $t->tgl,
                'pelanggan' => $t->customer?->nama, 'plat' => $t->vehicle?->plat,
                'total' => (float) $t->total, 'dibayar' => (float) $t->dibayar_sum,
                'sisa' => (float) $t->total - (float) $t->dibayar_sum,
            ]),
            'total' => (float) $piutang->sum(fn ($t) => $t->total - $t->dibayar_sum),
        ];
    }

    public function mekanik(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        return [
            'dari' => $dari, 'sampai' => $sampai,
            'data' => Transaction::where('transactions.branch_id', current_branch())
                ->where('transactions.status', '!=', 'batal')
                ->where('transactions.tipe', 'servis')->whereNotNull('transactions.mekanik_id')
                ->whereBetween('transactions.tgl', $this->win($dari, $sampai))
                ->leftJoin('tx_items', fn ($j) => $j->on('tx_items.transaction_id', '=', 'transactions.id')->where('tx_items.tipe', 'jasa'))
                ->join('users', 'users.id', '=', 'transactions.mekanik_id')
                ->selectRaw('users.name, COUNT(DISTINCT transactions.id) as jml_order, COALESCE(SUM(tx_items.subtotal),0) as nilai_jasa')
                ->groupBy('users.id', 'users.name')->orderByDesc('nilai_jasa')->get(),
        ];
    }

    public function kasir(Request $request)
    {
        $tgl = $request->get('tgl', Carbon::today()->toDateString());
        $b = current_branch();
        $win = $this->win($tgl, $tgl);
        $perMetode = Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, COUNT(*) as jml, SUM(jumlah) as total')->groupBy('metode')->get();

        return [
            'tgl' => $tgl,
            'perMetode' => $perMetode,
            'totalKas' => (float) $perMetode->sum('total'),
            'jmlTransaksi' => Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->count(),
            'omzet' => (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('total'),
            'pengeluaranTunai' => (float) Expense::where('branch_id', $b)->whereDate('tanggal', $tgl)->where('metode', 'tunai')->sum('nominal'),
        ];
    }

    public function konsolidasi(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $win = $this->win($dari, $sampai);
        $pendapatan = Transaction::aktif()->whereBetween('tgl', $win)->selectRaw('branch_id, SUM(total) as t')->groupBy('branch_id')->pluck('t', 'branch_id');
        $hpp = TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->join('parts', 'parts.id', '=', 'tx_items.ref_id')
            ->where('transactions.status', '!=', 'batal')->whereBetween('transactions.tgl', $win)
            ->selectRaw('transactions.branch_id, SUM(tx_items.qty * parts.harga_beli) as t')->groupBy('transactions.branch_id')->pluck('t', 'branch_id');
        $pengeluaran = Expense::whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)->selectRaw('branch_id, SUM(nominal) as t')->groupBy('branch_id')->pluck('t', 'branch_id');

        return [
            'dari' => $dari, 'sampai' => $sampai,
            'rows' => Branch::orderBy('nama')->get()->map(function ($b) use ($pendapatan, $hpp, $pengeluaran) {
                $pend = (float) ($pendapatan[$b->id] ?? 0);
                $h = (float) ($hpp[$b->id] ?? 0);
                $peng = (float) ($pengeluaran[$b->id] ?? 0);

                return ['nama' => $b->nama, 'pendapatan' => $pend, 'hpp' => $h, 'laba_kotor' => $pend - $h, 'pengeluaran' => $peng, 'laba_bersih' => $pend - $h - $peng];
            }),
        ];
    }
}
