@extends('layouts.app')
@section('title', 'Laporan Stok')
@section('header', 'Laporan Stok & Barang')

@section('content')
    @include('partials.date-filter')

    <div class="card p-5 mb-6 max-w-sm">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Nilai Persediaan Saat Ini</div>
        <div class="text-2xl font-bold text-emerald-600">{{ rupiah($nilaiPersediaan) }}</div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Part Terlaris (periode)</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Barang</th><th class="text-center">Qty</th><th class="text-right">Nilai</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($terlaris as $t)
                        <tr class="hover:bg-gray-50"><td>{{ $t->nama }}</td><td class="text-center font-medium">{{ (int) $t->qty }}</td><td class="text-right">{{ rupiah($t->total) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">Belum ada penjualan part.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Stok Menipis</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Barang</th><th class="text-center">Stok</th><th class="text-center">Min</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($menipis as $p)
                        <tr class="hover:bg-gray-50"><td>{{ $p->nama }}</td><td class="text-center text-rose-600 font-medium">{{ $p->stok }}</td><td class="text-center text-gray-400">{{ $p->stok_min }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">Semua stok aman 👍</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
