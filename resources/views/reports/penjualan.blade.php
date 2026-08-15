@extends('layouts.app')
@section('title', 'Laporan Penjualan')
@section('header', 'Laporan Penjualan')

@section('content')
    @include('partials.date-filter')

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Omzet</div><div class="text-2xl font-bold text-brand">{{ rupiah($totalOmzet) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Diskon Diberikan</div><div class="text-2xl font-bold text-rose-600">{{ rupiah($totalDiskon) }}</div></div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Per Platform / Channel</div>
            @forelse ($perPlatform->sortByDesc('total') as $row)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                    <span class="text-gray-600">{{ $row->platform?->nama ?? 'Tanpa platform' }} <span class="text-gray-400 text-xs">({{ $row->jml }})</span></span>
                    <span class="font-medium">{{ rupiah($row->total) }}</span>
                </div>
            @empty <p class="text-sm text-gray-400">Belum ada data.</p> @endforelse
        </div>
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Per Tipe</div>
            @forelse ($perTipe as $row)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                    <span class="capitalize text-gray-600">{{ $row->tipe }} <span class="text-gray-400 text-xs">({{ $row->jml }})</span></span>
                    <span class="font-medium">{{ rupiah($row->total) }}</span>
                </div>
            @empty <p class="text-sm text-gray-400">Belum ada data.</p> @endforelse
        </div>
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Per Metode Bayar</div>
            @forelse ($perMetode as $row)
                <div class="flex justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                    <span class="capitalize text-gray-600">{{ $row->metode }}</span>
                    <span class="font-medium">{{ rupiah($row->total) }}</span>
                </div>
            @empty <p class="text-sm text-gray-400">Belum ada data.</p> @endforelse
        </div>
    </div>
@endsection
