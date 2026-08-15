@extends('layouts.app')
@section('title', 'Piutang')
@section('header', 'Laporan Piutang / Bon')

@section('content')
    <div class="card p-5 mb-6 max-w-sm">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Piutang</div>
        <div class="text-2xl font-bold text-rose-600">{{ rupiah($total) }}</div>
    </div>

    <div class="card">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>No. Nota</th><th>Tanggal</th><th>Pelanggan</th><th>Kendaraan</th><th class="text-right">Total</th><th class="text-right">Dibayar</th><th class="text-right">Sisa</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($piutang as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="font-mono text-xs text-gray-500">{{ $t->no_nota }}</td>
                        <td class="text-gray-600 whitespace-nowrap">{{ $t->tgl?->format('d/m/Y') }}</td>
                        <td>{{ $t->customer?->nama ?? '-' }}</td>
                        <td class="text-gray-500">{{ $t->vehicle?->plat ?? '-' }}</td>
                        <td class="text-right">{{ rupiah($t->total) }}</td>
                        <td class="text-right text-gray-500">{{ rupiah($t->dibayar_sum) }}</td>
                        <td class="text-right font-semibold text-rose-600">{{ rupiah($t->total - $t->dibayar_sum) }}</td>
                        <td class="text-right"><a href="{{ route('transactions.show', $t->id) }}" class="text-brand hover:underline">Bayar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-gray-400">Tidak ada piutang. Semua lunas 👍</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
