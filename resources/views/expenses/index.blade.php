@extends('layouts.app')
@section('title', 'Pengeluaran')
@section('header', 'Pengeluaran')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="card p-5 lg:col-span-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Periode</div>
            <div class="text-2xl font-bold text-rose-600">{{ rupiah($total) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($dari)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($sampai)->format('d/m/Y') }}</div>
        </div>
        <div class="card p-5 lg:col-span-2">
            <div class="text-sm font-medium text-gray-700 mb-3">Per Kategori</div>
            @forelse ($perKategori->sortByDesc('total') as $row)
                <div class="flex justify-between py-1 text-sm border-b border-gray-100 last:border-0">
                    <span class="text-gray-600">{{ $row->category?->nama ?? 'Tanpa kategori' }}</span>
                    <span class="font-medium">{{ rupiah($row->total) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada pengeluaran di periode ini.</p>
            @endforelse
        </div>
    </div>

    <div class="card p-5">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <div><label class="block text-xs text-gray-500 mb-1">Dari</label><input type="date" name="dari" value="{{ $dari }}" class="form-input"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Sampai</label><input type="date" name="sampai" value="{{ $sampai }}" class="form-input"></div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Kategori</label>
                    <select name="cat" class="form-input">
                        <option value="">semua</option>
                        @foreach ($cats as $c)<option value="{{ $c->id }}" @selected(request('cat') == $c->id)>{{ $c->nama }}</option>@endforeach
                    </select>
                </div>
                <button class="btn-secondary btn-sm">Filter</button>
            </form>
            <a href="{{ route('expenses.create') }}" class="btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Pengeluaran</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Metode</th><th class="text-right">Nominal</th><th>Bukti</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($expenses as $e)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap text-gray-600">{{ $e->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $e->category?->nama ?? '-' }}</td>
                        <td>{{ $e->keterangan }} @if($e->ref_tipe === 'salary')<span class="badge badge-blue ml-1">gaji</span>@endif</td>
                        <td class="capitalize text-gray-500">{{ $e->metode }}</td>
                        <td class="text-right font-medium">{{ rupiah($e->nominal) }}</td>
                        <td>@if($e->bukti)<a href="{{ asset('storage/'.$e->bukti) }}" target="_blank" class="text-brand hover:underline">lihat</a>@else <span class="text-gray-300">-</span>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($e->ref_tipe !== 'salary')
                                <a href="{{ route('expenses.edit', $e) }}" class="text-brand hover:underline">Edit</a>
                                <form action="{{ route('expenses.destroy', $e) }}" method="POST" class="inline" data-confirm="Hapus pengeluaran ini?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            @else
                                <span class="text-gray-300 text-xs">via Karyawan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada pengeluaran.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $expenses->links() }}</div>
    </div>
@endsection
