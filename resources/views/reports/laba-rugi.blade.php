@extends('layouts.app')
@section('title', 'Laba Rugi')
@section('header', 'Laporan Laba Rugi')

@section('content')
    @include('partials.date-filter')

    <div class="card max-w-2xl">
        <div class="px-5 py-4 border-b">
            <div class="text-sm text-gray-500">Periode</div>
            <div class="font-medium">{{ \Illuminate\Support\Carbon::parse($dari)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($sampai)->format('d M Y') }}</div>
        </div>
        <div class="p-5 space-y-2 text-sm">
            <div class="flex justify-between py-1"><span class="text-gray-600">Pendapatan (omzet)</span><span class="font-medium">{{ rupiah($pendapatan) }}</span></div>
            <div class="flex justify-between py-1"><span class="text-gray-600">HPP (harga beli part terjual)</span><span class="text-rose-600">− {{ rupiah($hpp) }}</span></div>
            <div class="flex justify-between py-2 border-t border-b font-semibold"><span>Laba Kotor</span><span class="text-brand">{{ rupiah($labaKotor) }}</span></div>

            <div class="pt-2 text-gray-500 uppercase text-xs tracking-wider">Pengeluaran Operasional</div>
            @forelse ($pengeluaran->sortByDesc('total') as $row)
                <div class="flex justify-between py-1"><span class="text-gray-600 pl-3">{{ $row->category?->nama ?? 'Lainnya' }}</span><span class="text-rose-600">− {{ rupiah($row->total) }}</span></div>
            @empty
                <div class="text-gray-400 pl-3 py-1">Tidak ada pengeluaran.</div>
            @endforelse
            <div class="flex justify-between py-1 border-t"><span class="text-gray-600">Total Pengeluaran</span><span class="text-rose-600 font-medium">− {{ rupiah($totalPengeluaran) }}</span></div>

            <div class="flex justify-between py-3 border-t-2 border-gray-300 text-lg font-bold">
                <span>Laba Bersih</span>
                <span class="{{ $labaBersih < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ rupiah($labaBersih) }}</span>
            </div>
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-3 max-w-2xl">Catatan: pembelian sparepart tidak dihitung sebagai pengeluaran di sini — biayanya masuk lewat HPP saat barang terjual (akuntansi kas sederhana).</p>
@endsection
