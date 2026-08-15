@extends('layouts.app')
@section('title', 'Arus Kas')
@section('header', 'Laporan Arus Kas')

@section('content')
    @include('partials.date-filter')

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kas Masuk</div><div class="text-2xl font-bold text-emerald-600">{{ rupiah($kasMasuk) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Kas Keluar</div><div class="text-2xl font-bold text-rose-600">{{ rupiah($kasKeluar) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Arus Kas Bersih</div><div class="text-2xl font-bold {{ ($kasMasuk-$kasKeluar) < 0 ? 'text-rose-600' : 'text-brand' }}">{{ rupiah($kasMasuk - $kasKeluar) }}</div></div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Kas Masuk per Metode</div>
            @forelse ($masukPerMetode as $metode => $total)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-100 last:border-0"><span class="capitalize text-gray-600">{{ $metode }}</span><span class="font-medium">{{ rupiah($total) }}</span></div>
            @empty
                <p class="text-sm text-gray-400">Belum ada penerimaan.</p>
            @endforelse
        </div>
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Kas Keluar</div>
            <div class="flex justify-between py-1.5 text-sm border-b border-gray-100"><span class="text-gray-600">Pengeluaran operasional (+ gaji)</span><span class="font-medium">{{ rupiah($pengeluaran) }}</span></div>
            <div class="flex justify-between py-1.5 text-sm"><span class="text-gray-600">Pembelian sparepart</span><span class="font-medium">{{ rupiah($pembelian) }}</span></div>
        </div>
    </div>
@endsection
