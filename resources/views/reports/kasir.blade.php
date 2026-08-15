@extends('layouts.app')
@section('title', 'Tutup Kasir')
@section('header', 'Tutup Kasir Harian')

@section('content')
    <form method="GET" class="flex gap-2 items-end mb-6">
        <div><label class="block text-xs text-gray-500 mb-1">Tanggal</label><input type="date" name="tgl" value="{{ $tgl }}" class="form-input"></div>
        <button class="btn-secondary btn-sm">Tampilkan</button>
    </form>

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Omzet</div><div class="text-2xl font-bold text-brand">{{ rupiah($omzet) }}</div><div class="text-xs text-gray-400 mt-1">{{ $jmlTransaksi }} transaksi</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kas Diterima</div><div class="text-2xl font-bold text-emerald-600">{{ rupiah($totalKas) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Pengeluaran Tunai</div><div class="text-2xl font-bold text-rose-600">{{ rupiah($pengeluaranTunai) }}</div></div>
    </div>

    <div class="card max-w-lg">
        <div class="px-5 py-3 border-b font-semibold text-gray-700">Rincian Kas per Metode</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Metode</th><th class="text-center">Jumlah</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($perMetode as $row)
                    <tr class="hover:bg-gray-50"><td class="capitalize">{{ $row->metode }}</td><td class="text-center text-gray-500">{{ $row->jml }}</td><td class="text-right font-medium">{{ rupiah($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-gray-400">Belum ada penerimaan hari ini.</td></tr>
                @endforelse
            </tbody>
            @if ($perMetode->count())
                <tfoot><tr class="border-t"><td colspan="2" class="px-4 py-3 text-right font-semibold">Total Kas Fisik + Non-tunai</td><td class="px-4 py-3 text-right font-bold text-brand">{{ rupiah($totalKas) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
    <div class="card p-5 max-w-lg mt-4 bg-gray-50">
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Perkiraan kas tunai di laci (tunai − pengeluaran tunai)</span>
            <span class="font-bold">{{ rupiah(($perMetode->firstWhere('metode','tunai')->total ?? 0) - $pengeluaranTunai) }}</span>
        </div>
    </div>
@endsection
