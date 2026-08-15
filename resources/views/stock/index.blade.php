@extends('layouts.app')
@section('title', 'Stok')
@section('header', 'Stok & Opname')

@section('content')
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-emerald-600 text-white p-4 shadow-sm">
            <div class="text-sm opacity-80">Nilai Persediaan</div>
            <div class="text-2xl font-bold mt-1">{{ rupiah($nilaiPersediaan) }}</div>
            <div class="text-xs opacity-70 mt-1">stok × harga beli</div>
        </div>
        <div class="rounded-xl bg-rose-600 text-white p-4 shadow-sm">
            <div class="text-sm opacity-80">Stok Menipis</div>
            <div class="text-2xl font-bold mt-1">{{ $jumlahMenipis }}</div>
            <div class="text-xs opacity-70 mt-1">item di bawah minimum</div>
        </div>
        <div class="card p-4 flex flex-col justify-center gap-2">
            <a href="{{ route('stock.opname') }}" class="btn-primary">@include('partials.icon', ['name' => 'clipboard', 'class' => 'w-4 h-4']) Stok Opname</a>
            <a href="{{ route('stock.moves') }}" class="btn-secondary">@include('partials.icon', ['name' => 'refresh', 'class' => 'w-4 h-4']) Semua Mutasi</a>
        </div>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex gap-2 items-center mb-4">
            <input name="q" value="{{ $q }}" placeholder="Cari nama / kode…" class="rounded-lg border-gray-300 text-sm">
            <label class="text-sm flex items-center gap-1">
                <input type="checkbox" name="low" value="1" @checked(request('low')) onchange="this.form.submit()"> stok menipis
            </label>
            <button class="btn-secondary btn-sm">Cari</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Kode</th><th>Nama</th><th>Rak</th><th class="text-center">Stok</th><th class="text-center">Min</th><th class="text-right">Nilai</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($parts as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-mono text-xs">{{ $p->kode }}</td>
                            <td class="font-medium">{{ $p->nama }}</td>
                            <td class="text-gray-500">{{ $p->lokasi_rak ?: '-' }}</td>
                            <td class="text-center {{ $p->stok <= $p->stok_min ? 'text-red-600 font-semibold' : '' }}">
                                {{ $p->stok }} {{ $p->satuan }}
                            </td>
                            <td class="text-center text-gray-400">{{ $p->stok_min }}</td>
                            <td class="text-right">{{ rupiah($p->stok * $p->harga_beli) }}</td>
                            <td class="text-right"><a href="{{ route('stock.card', $p) }}" class="text-brand hover:underline">Kartu Stok</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $parts->links() }}</div>
    </div>
@endsection
