@extends('layouts.app')
@section('title', 'Retur')
@section('header', 'Retur Penjualan / Servis')

@section('content')
    <div class="card p-5">
        <p class="text-sm text-gray-500 mb-4">Retur dibuat dari halaman <strong>detail transaksi</strong> → tombol "Retur". Part yang diretur otomatis dikembalikan ke stok.</p>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>No. Retur</th><th>Tanggal</th><th>Transaksi</th><th>Pelanggan</th><th>Alasan</th><th class="text-right">Total</th><th>Oleh</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($returns as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="font-mono text-xs"><a href="{{ route('returns.show', $r) }}" class="text-brand hover:underline">{{ $r->no }}</a></td>
                        <td class="text-gray-600 whitespace-nowrap">{{ $r->tgl?->format('d/m/Y H:i') }}</td>
                        <td class="font-mono text-xs text-gray-500">{{ $r->transaction?->no_nota }}</td>
                        <td>{{ $r->transaction?->customer?->nama ?? '-' }}</td>
                        <td class="text-gray-600">{{ $r->alasan }}</td>
                        <td class="text-right font-medium text-rose-600">{{ rupiah($r->total) }}</td>
                        <td class="text-gray-500">{{ $r->user?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada retur.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $returns->links() }}</div>
    </div>
@endsection
