<?php

namespace App\Http\Controllers;

use App\Models\Expense;
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

    /** HPP (harga beli) part yang terjual dalam rentang. */
    private function hpp(string $dari, string $sampai): float
    {
        return (float) TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->join('parts', 'parts.id', '=', 'tx_items.ref_id')
            ->where('transactions.status', '!=', 'batal')
            ->whereBetween('transactions.tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->sum(DB::raw('tx_items.qty * parts.harga_beli'));
    }

    public function labaRugi(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        $pendapatan = (float) Transaction::aktif()->whereBetween('tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->sum('total');
        $hpp = $this->hpp($dari, $sampai);
        $labaKotor = $pendapatan - $hpp;

        $pengeluaran = Expense::whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->selectRaw('COALESCE(SUM(nominal),0) as total, expense_cat_id')
            ->groupBy('expense_cat_id')->with('category')->get();
        $totalPengeluaran = (float) $pengeluaran->sum('total');

        $labaBersih = $labaKotor - $totalPengeluaran;

        return view('reports.laba-rugi', compact('dari', 'sampai', 'pendapatan', 'hpp', 'labaKotor', 'pengeluaran', 'totalPengeluaran', 'labaBersih'));
    }

    public function arusKas(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        $kasMasuk = (float) Payment::whereBetween('tgl_bayar', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])->sum('jumlah');
        $pengeluaran = (float) Expense::whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)->sum('nominal');
        $pembelian = (float) Purchase::whereDate('tgl', '>=', $dari)->whereDate('tgl', '<=', $sampai)->sum('total');
        $kasKeluar = $pengeluaran + $pembelian;

        $masukPerMetode = Payment::whereBetween('tgl_bayar', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->pluck('total', 'metode');

        return view('reports.arus-kas', compact('dari', 'sampai', 'kasMasuk', 'kasKeluar', 'pengeluaran', 'pembelian', 'masukPerMetode'));
    }

    public function penjualan(Request $request)
    {
        [$dari, $sampai] = $this->range($request);
        $win = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $perPlatform = Transaction::aktif()->whereBetween('tgl', $win)
            ->selectRaw('platform_id, COUNT(*) as jml, SUM(total) as total')
            ->groupBy('platform_id')->with('platform')->get();

        $perTipe = Transaction::aktif()->whereBetween('tgl', $win)
            ->selectRaw('tipe, COUNT(*) as jml, SUM(total) as total')->groupBy('tipe')->get();

        $perMetode = Payment::whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, SUM(jumlah) as total')->groupBy('metode')->get();

        $totalDiskon = (float) Transaction::aktif()->whereBetween('tgl', $win)->sum('diskon');
        $totalOmzet = (float) Transaction::aktif()->whereBetween('tgl', $win)->sum('total');

        return view('reports.penjualan', compact('dari', 'sampai', 'perPlatform', 'perTipe', 'perMetode', 'totalDiskon', 'totalOmzet'));
    }

    public function stok(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        $terlaris = TxItem::where('tx_items.tipe', 'part')
            ->join('transactions', 'transactions.id', '=', 'tx_items.transaction_id')
            ->where('transactions.status', '!=', 'batal')
            ->whereBetween('transactions.tgl', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->selectRaw('tx_items.nama, SUM(tx_items.qty) as qty, SUM(tx_items.subtotal) as total')
            ->groupBy('tx_items.nama')->orderByDesc('qty')->limit(20)->get();

        $nilaiPersediaan = (float) Part::selectRaw('SUM(stok * harga_beli) as t')->value('t');
        $menipis = Part::lowStock()->orderBy('stok')->get();

        return view('reports.stok', compact('dari', 'sampai', 'terlaris', 'nilaiPersediaan', 'menipis'));
    }

    public function piutang()
    {
        $piutang = Transaction::aktif()
            ->leftJoin('payments', 'payments.transaction_id', '=', 'transactions.id')
            ->selectRaw('transactions.*, COALESCE(SUM(payments.jumlah),0) as dibayar_sum')
            ->groupBy('transactions.id')
            ->havingRaw('transactions.total - COALESCE(SUM(payments.jumlah),0) > 0')
            ->with('customer', 'vehicle')
            ->orderBy('transactions.tgl')->get();

        $total = $piutang->sum(fn ($t) => $t->total - $t->dibayar_sum);

        return view('reports.piutang', compact('piutang', 'total'));
    }

    public function mekanik(Request $request)
    {
        [$dari, $sampai] = $this->range($request);

        $data = Transaction::where('transactions.status', '!=', 'batal')
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

    public function kasir(Request $request)
    {
        $tgl = $request->get('tgl', Carbon::today()->toDateString());
        $win = [$tgl . ' 00:00:00', $tgl . ' 23:59:59'];

        $perMetode = Payment::whereBetween('tgl_bayar', $win)
            ->selectRaw('metode, COUNT(*) as jml, SUM(jumlah) as total')->groupBy('metode')->get();

        $totalKas = (float) $perMetode->sum('total');
        $jmlTransaksi = Transaction::aktif()->whereBetween('tgl', $win)->count();
        $omzet = (float) Transaction::aktif()->whereBetween('tgl', $win)->sum('total');
        $pengeluaranTunai = (float) Expense::whereDate('tanggal', $tgl)->where('metode', 'tunai')->sum('nominal');

        return view('reports.kasir', compact('tgl', 'perMetode', 'totalKas', 'jmlTransaksi', 'omzet', 'pengeluaranTunai'));
    }
}
