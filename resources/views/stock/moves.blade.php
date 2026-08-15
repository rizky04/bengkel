@extends('layouts.app')
@section('title', 'Mutasi Stok')
@section('header', 'Semua Mutasi Stok')

@section('content')
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-2 items-end mb-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipe</label>
                <select name="tipe" class="rounded-lg border-gray-300 text-sm">
                    <option value="">— semua —</option>
                    @foreach (['in' => 'Masuk', 'out' => 'Keluar', 'adjust' => 'Penyesuaian'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('tipe') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="rounded-lg border-gray-300 text-sm">
            </div>
            <button class="btn-secondary btn-sm">Filter</button>
            <a href="{{ route('stock.moves') }}" class="px-3 py-2 text-sm text-gray-500 hover:underline">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Tanggal</th><th>Barang</th><th>Tipe</th><th>Keterangan</th><th>Oleh</th><th class="text-center">Qty</th><th class="text-center">Saldo</th></tr>
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
                            <td>
                                <a href="{{ route('stock.card', $m->part_id) }}" class="text-brand hover:underline">
                                    {{ $m->part?->nama }}
                                </a>
                            </td>
                            <td><span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ $m->tipe }}</span></td>
                            <td class="text-gray-600">{{ $m->keterangan }}</td>
                            <td class="text-gray-500">{{ $m->user?->name ?? '-' }}</td>
                            <td class="text-center font-medium {{ $m->qty < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $m->qty > 0 ? '+' : '' }}{{ $m->qty }}
                            </td>
                            <td class="text-center font-semibold">{{ $m->saldo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $moves->links() }}</div>
    </div>
@endsection
