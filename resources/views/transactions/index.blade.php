@extends('layouts.app')
@section('title', 'Transaksi')
@section('header', 'Transaksi')

@section('content')
    @php
        $badge = fn ($s) => [
            'antri' => 'bg-gray-100 text-gray-700', 'dikerjakan' => 'bg-blue-100 text-blue-700',
            'selesai' => 'bg-amber-100 text-amber-700', 'lunas' => 'bg-emerald-100 text-emerald-700',
            'batal' => 'bg-rose-100 text-rose-700',
        ][$s] ?? 'bg-gray-100';
    @endphp

    <div class="card p-5">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="No. nota…" class="rounded-lg border-gray-300 text-sm">
                <select name="tipe" class="rounded-lg border-gray-300 text-sm">
                    <option value="">semua tipe</option>
                    <option value="servis" @selected(($filters['tipe'] ?? '') === 'servis')>Servis</option>
                    <option value="penjualan" @selected(($filters['tipe'] ?? '') === 'penjualan')>Penjualan</option>
                </select>
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">semua status</option>
                    @foreach (['antri','dikerjakan','selesai','lunas','batal'] as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="rounded-lg border-gray-300 text-sm">
                <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Filter</button>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('transactions.export', request()->query()) }}" class="btn-secondary btn-sm">⬇ Export</a>
                <a href="{{ route('pos.create') }}" class="btn-primary">+ Transaksi Baru</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">No. Nota</th><th>Tanggal</th><th>Tipe</th><th>Pelanggan / Kendaraan</th><th>Status</th><th class="text-right">Total</th><th class="text-right">Sisa</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($transactions as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="font-mono text-xs text-gray-500">{{ $t->no_nota }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $t->tgl?->format('d/m/Y H:i') }}</td>
                            <td class="capitalize">{{ $t->tipe }}</td>
                            <td>
                                {{ $t->customer?->nama ?? '-' }}
                                @if ($t->vehicle)<span class="text-gray-400 text-xs">/ {{ $t->vehicle->plat }}</span>@endif
                            </td>
                            <td>@include('partials.status-pill', ['status' => $t->status])</td>
                            <td class="text-right font-medium">{{ rupiah($t->total) }}</td>
                            <td class="text-right {{ $t->sisa > 0 && $t->status !== 'batal' ? 'text-rose-600' : 'text-gray-300' }}">
                                {{ $t->status === 'batal' ? '-' : rupiah($t->sisa) }}
                            </td>
                            <td class="text-right"><a href="{{ route('transactions.show', $t) }}" class="text-brand hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-gray-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
@endsection
