@extends('layouts.app')
@section('title', 'Kartu Stok')
@section('header', 'Kartu Stok — ' . $part->nama)

@section('content')
    <div class="card p-5 mb-4">
        <div class="grid md:grid-cols-4 gap-4 text-sm">
            <div><div class="text-gray-500">Kode</div><div class="font-mono font-medium">{{ $part->kode }}</div></div>
            <div><div class="text-gray-500">Stok Saat Ini</div><div class="font-bold text-lg">{{ $part->stok }} {{ $part->satuan }}</div></div>
            <div><div class="text-gray-500">Stok Minimum</div><div class="font-medium">{{ $part->stok_min }}</div></div>
            <div><div class="text-gray-500">Nilai Persediaan</div><div class="font-medium">{{ rupiah($part->stok * $part->harga_beli) }}</div></div>
        </div>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-2 items-end mb-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <button class="btn-secondary btn-sm">Filter</button>
            <a href="{{ route('stock.card', $part) }}" class="px-3 py-2 text-sm text-gray-500 hover:underline">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Tanggal</th><th>Tipe</th><th>Keterangan</th><th>Oleh</th><th class="text-center">Masuk</th><th class="text-center">Keluar</th><th class="text-center">Saldo</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($moves as $m)
                        @php
                            $badge = match ($m->tipe) {
                                'in' => 'bg-emerald-100 text-emerald-700',
                                'out' => 'bg-rose-100 text-rose-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 whitespace-nowrap">{{ $m->tgl?->format('d/m/Y H:i') }}</td>
                            <td><span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ $m->tipe }}</span></td>
                            <td class="text-gray-600">{{ $m->keterangan }}</td>
                            <td class="text-gray-500">{{ $m->user?->name ?? '-' }}</td>
                            <td class="text-center text-emerald-600 font-medium">{{ $m->qty > 0 ? '+' . $m->qty : '' }}</td>
                            <td class="text-center text-rose-600 font-medium">{{ $m->qty < 0 ? $m->qty : '' }}</td>
                            <td class="text-center font-semibold">{{ $m->saldo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $moves->links() }}</div>

        <a href="{{ route('stock.index') }}" class="inline-block mt-4 btn-secondary">Kembali</a>
    </div>
@endsection
