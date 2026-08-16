@extends('layouts.app')
@section('title', 'Pembelian')
@section('header', 'Pembelian / Barang Masuk')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="No. pembelian…" class="rounded-lg border-gray-300 text-sm">
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">semua status</option>
                    @foreach (['lunas' => 'Lunas', 'belum_lunas' => 'Belum Lunas'] as $k => $lbl)
                        <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="rounded-lg border-gray-300 text-sm">
                <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Filter</button>
            </form>
            <a href="{{ route('purchases.create') }}" class="btn-primary">+ Pembelian</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">No.</th><th>Tanggal</th><th>Supplier</th><th class="text-center">Item</th><th>Status</th><th class="text-right">Total</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($purchases as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-mono text-xs text-gray-500">{{ $p->no }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $p->tgl?->format('d/m/Y') }}</td>
                            <td>{{ $p->supplier?->nama ?? '-' }}</td>
                            <td class="text-center">{{ $p->items_count }}</td>
                            <td>@include('partials.status-pill', ['status' => $p->status])</td>
                            <td class="text-right font-medium">{{ rupiah($p->total) }}</td>
                            <td class="text-right"><a href="{{ route('purchases.show', $p) }}" class="text-brand hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>
@endsection
