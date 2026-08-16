@extends('layouts.app')
@section('title', 'Laporan')
@section('header', 'Laporan')

@section('content')
    @php
        $reports = [
            ['reports.laba-rugi', 'Laba Rugi', 'Pendapatan − HPP − pengeluaran', 'chart'],
            ['reports.arus-kas', 'Arus Kas', 'Kas masuk vs keluar', 'cash'],
            ['reports.penjualan', 'Penjualan', 'Per platform, tipe & metode bayar', 'receipt'],
            ['reports.stok', 'Stok & Barang', 'Part terlaris, nilai persediaan', 'cube'],
            ['reports.piutang', 'Piutang / Bon', 'Transaksi belum lunas', 'users'],
            ['reports.mekanik', 'Produktivitas Mekanik', 'Order & nilai jasa per mekanik', 'wrench'],
            ['reports.kasir', 'Tutup Kasir', 'Rekap kas harian per metode', 'clipboard'],
            ['reports.konsolidasi', 'Konsolidasi', 'Laba-rugi gabungan semua cabang', 'store'],
        ];
    @endphp
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($reports as [$route, $judul, $desc, $icon])
            <a href="{{ route($route) }}" class="card p-5 hover:border-brand hover:shadow-sm transition group">
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center group-hover:bg-brand group-hover:text-white transition">
                        @include('partials.icon', ['name' => $icon, 'class' => 'w-5 h-5'])
                    </span>
                    <div>
                        <div class="font-semibold text-gray-800">{{ $judul }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $desc }}</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endsection
