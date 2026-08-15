@extends('layouts.app')
@section('title', 'Pembelian')
@section('header', 'Pembelian / Barang Masuk')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <form method="GET" class="flex gap-2">
                <input name="q" value="{{ $q }}" placeholder="Cari no. pembelian…" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Cari</button>
            </form>
            <a href="{{ route('purchases.create') }}" class="btn-primary">+ Pembelian</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">No.</th><th>Tanggal</th><th>Supplier</th><th class="text-center">Item</th><th class="text-right">Total</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($purchases as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-mono text-xs">{{ $p->no }}</td>
                            <td>{{ $p->tgl?->format('d/m/Y') }}</td>
                            <td>{{ $p->supplier?->nama ?? '-' }}</td>
                            <td class="text-center">{{ $p->items()->count() }}</td>
                            <td class="text-right font-medium">{{ rupiah($p->total) }}</td>
                            <td class="text-right"><a href="{{ route('purchases.show', $p) }}" class="text-brand hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-400">Belum ada pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>
@endsection
