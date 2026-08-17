<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\TxItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    /** Rentang tanggal default: bulan berjalan. */
    private function range(Request $request): array
    {
        return [
            $request->get('dari', Carbon::now()->startOfMonth()->toDateString()),
            $request->get('sampai', Carbon::now()->toDateString()),
        ];
    }

    /** HPP (harga beli) part yang terjual dalam rentang (cabang aktif). */
    private function hpp(string $dari, string $sampai): float
    {
        return (float) TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->join('parts', 'parts.id', '=', 'tx_items.ref_id')
            ->where('transactions.branch_id', current_branch())
            ->where('transactions.status', '!=', 'batal')
            ->whereBetween('transactions.tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->sum(DB::raw('tx_items.qty * parts.harga_beli'));
    }

    /** Total retur & HPP barang yang diretur (dikembalikan ke stok) dalam rentang. */
    private function retur(string $dari, string $sampai): array
    {
        $b = current_branch();
        $win = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $total = (float) \App\Models\SalesReturn::where('branch_id', $b)->whereBetween('tgl', $win)->sum('total');
        $hpp = (float) \App\Models\ReturnItem::whereNotNull('part_id')
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

        $bruto = (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->sum('total');
        $retur = $this->retur($dari, $sampai);
        $pendapatan = $bruto - $retur['total'];            // pendapatan bersih setelah retur
        $hpp = $this->hpp($dari, $sampai) - $retur['hpp']; // HPP bersih (barang retur balik ke stok)
        $labaKotor = $pendapatan - $hpp;

        $pengeluaran = Expense::where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->selectRaw('COALESCE(SUM(nominal),0) as total, expense_cat_id')
            ->groupBy('expense_cat_id')->with('category')->get();
        $totalPengeluaran = (float) $pengeluaran->sum('total');

        $labaBersih = $labaKotor - $totalPengeluaran;

        $returTotal = $retur['total'];

        return view('reports.laba-rugi', compact('dari', 'sampai', 'bruto', 'returTotal', 'pendapatan', 'hpp', 'labaKotor', 'pengeluaran', 'totalPengeluaran', 'labaBersih'));
    }

    public function arusKas(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();
        $win = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $kasMasuk = (float) Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)->sum('jumlah');
        $pengeluaran = (float) Expense::where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)->sum('nominal');
        $pembelian = (float) Purchase::where('branch_id', $b)->whereDate('tgl', '>=', $dari)->whereDate('tgl', '<=', $sampai)->sum('total');
        $refundRetur = $this->retur($dari, $sampai)['total'];
        $kasKeluar = $pengeluaran + $pembelian + $refundRetur;

        $masukPerMetode = Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->pluck('total', 'metode');

        return view('reports.arus-kas', compact('dari', 'sampai', 'kasMasuk', 'kasKeluar', 'pengeluaran', 'pembelian', 'refundRetur', 'masukPerMetode'));
    }

    public function penjualan(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();
        $win = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $perPlatform = Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)
            ->selectRaw('platform_id, COUNT(*) as jml, SUM(total) as total')
            ->groupBy('platform_id')->with('platform')->get();

        $perTipe = Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)
            ->selectRaw('tipe, COUNT(*) as jml, SUM(total) as total')->groupBy('tipe')->get();

        $perMetode = Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->get();

        $totalDiskon = (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('diskon');
        $totalOmzet = (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('total');

        return view('reports.penjualan', compact('dari', 'sampai', 'perPlatform', 'perTipe', 'perMetode', 'totalDiskon', 'totalOmzet'));
    }

    public function stok(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $b = current_branch();

        $terlaris = TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->where('transactions.branch_id', $b)
            ->where('transactions.status', '!=', 'batal')
            ->whereBetween('transactions.tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->selectRaw('tx_items.nama, SUM(tx_items.qty) as qty, SUM(tx_items.subtotal) as total')
            ->groupBy('tx_items.nama')->orderByDesc('qty')->limit(20)->get();

        $nilaiPersediaan = (float) Inventory::where('branch_id', $b)
            ->join('parts', 'parts.id', '=', 'inventories.part_id')
            ->sum(DB::raw('inventories.stok * parts.harga_beli'));
        $menipis = Part::withStok($b)->lowStock($b)->orderBy('stok')->get();

        return view('reports.stok', compact('dari', 'sampai', 'terlaris', 'nilaiPersediaan', 'menipis'));
    }

    public function piutang()
    {
        $piutang = Transaction::aktif()->where('transactions.branch_id', current_branch())
            ->selectRaw('transactions.*, COALESCE((SELECT SUM(jumlah) FROM payments WHERE payments.transaction_id = transactions.id), 0) as dibayar_sum')
            ->whereRaw('transactions.total - COALESCE((SELECT SUM(jumlah) FROM payments WHERE payments.transaction_id = transactions.id), 0) > 0')
            ->with('customer', 'vehicle')
            ->orderBy('transactions.tgl')->get();

        $total = $piutang->sum(fn ($t) => $t->total - $t->dibayar_sum);

        return view('reports.piutang', compact('piutang', 'total'));
    }

    public function mekanik(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        $data = Transaction::where('transactions.branch_id', current_branch())
            ->where('transactions.status', '!=', 'batal')
            ->where('transactions.tipe', 'servis')->whereNotNull('transactions.mekanik_id')
            ->whereBetween('transactions.tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->leftJoin('tx_items', function ($j) {
                $j->on('tx_items.transaction_id', '=', 'transactions.id')->where('tx_items.tipe', 'jasa');
            })
            ->join('users', 'users.id', '=', 'transactions.mekanik_id')
            ->selectRaw('users.name, COUNT(DISTINCT transactions.id) as jml_order, COALESCE(SUM(tx_items.subtotal),0) as nilai_jasa')
            ->groupBy('users.id', 'users.name')->orderByDesc('nilai_jasa')->get();

        return view('reports.mekanik', compact('dari', 'sampai', 'data'));
    }

    /** Konsolidasi lintas cabang (tidak di-scope ke cabang aktif). */
    public function konsolidasi(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $win = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $pendapatan = Transaction::aktif()->whereBetween('tgl', $win)
            ->selectRaw('branch_id, SUM(total) as t')->groupBy('branch_id')->pluck('t', 'branch_id');

        $hpp = TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->join('parts', 'parts.id', '=', 'tx_items.ref_id')
            ->where('transactions.status', '!=', 'batal')->whereBetween('transactions.tgl', $win)
            ->selectRaw('transactions.branch_id, SUM(tx_items.qty * parts.harga_beli) as t')
            ->groupBy('transactions.branch_id')->pluck('t', 'branch_id');

        $pengeluaran = Expense::whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->selectRaw('branch_id, SUM(nominal) as t')->groupBy('branch_id')->pluck('t', 'branch_id');

        $rows = \App\Models\Branch::orderBy('nama')->get()->map(function ($b) use ($pendapatan, $hpp, $pengeluaran) {
            $pend = (float) ($pendapatan[$b->id] ?? 0);
            $h = (float) ($hpp[$b->id] ?? 0);
            $peng = (float) ($pengeluaran[$b->id] ?? 0);
            $labaKotor = $pend - $h;

            return (object) [
                'nama' => $b->nama, 'pendapatan' => $pend, 'hpp' => $h,
                'laba_kotor' => $labaKotor, 'pengeluaran' => $peng, 'laba_bersih' => $labaKotor - $peng,
            ];
        });

        return view('reports.konsolidasi', compact('dari', 'sampai', 'rows'));
    }

    public function kasir(Request $request)
    {
        $tgl = $request->get('tgl', Carbon::today()->toDateString());
        $b = current_branch();
        $win = [$tgl . ' 00:00:00', $tgl . ' 23:59:59'];

        $perMetode = Payment::where('branch_id', $b)->whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, COUNT(*) as jml, SUM(jumlah) as total')->groupBy('metode')->get();

        $totalKas = (float) $perMetode->sum('total');
        $jmlTransaksi = Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->count();
        $omzet = (float) Transaction::aktif()->where('branch_id', $b)->whereBetween('tgl', $win)->sum('total');
        $pengeluaranTunai = (float) Expense::where('branch_id', $b)->whereDate('tanggal', $tgl)->where('metode', 'tunai')->sum('nominal');

        return view('reports.kasir', compact('tgl', 'perMetode', 'totalKas', 'jmlTransaksi', 'omzet', 'pengeluaranTunai'));
    }
}
