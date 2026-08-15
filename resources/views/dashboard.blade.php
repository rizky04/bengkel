@extends('layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $stats = [
                ['Omzet Hari Ini', rupiah($omzetHari), $trxHari.' transaksi', 'text-brand'],
                ['Omzet Bulan Ini', rupiah($omzetBulan), 'akumulasi bulan ini', 'text-emerald-600'],
                ['Pengeluaran Bulan Ini', rupiah($pengeluaranBulan), 'operasional', 'text-rose-600'],
                ['Order Aktif', $orderAktif, 'antri / dikerjakan', 'text-amber-600'],
            ];
        @endphp
        @foreach ($stats as [$label, $val, $sub, $color])
            <div class="card p-5">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</div>
                <div class="text-2xl font-bold {{ $color }}">{{ $val }}</div>
                @if ($sub)<div class="text-xs text-gray-400 mt-1">{{ $sub }}</div>@endif
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Omzet 7 Hari Terakhir</div>
            <div class="p-5"><canvas id="omzetChart" height="110"></canvas></div>
        </div>

        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Stok Menipis</div>
            <div class="p-5">
                @forelse ($lowStock as $p)
                    <div class="flex justify-between py-1.5 text-sm border-b last:border-0">
                        <span>{{ $p->nama }}</span>
                        <span class="text-red-600 font-semibold">{{ $p->stok }}/{{ $p->stok_min }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Semua stok aman 👍</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-6 p-5">
        <div class="flex justify-between items-center text-sm">
            <span class="text-gray-500">Total Piutang / Bon Belum Lunas</span>
            <span class="text-lg font-bold text-rose-600">{{ rupiah($piutang) }}</span>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        new Chart(document.getElementById('omzetChart'), {
            type: 'bar',
            data: {
                labels: @json($chart->pluck('label')),
                datasets: [{ label: 'Omzet', data: @json($chart->pluck('total')),
                    backgroundColor: '#2563eb', borderRadius: 6 }]
            },
            options: { plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp' + v.toLocaleString('id') } } } }
        });
    </script>
@endpush
