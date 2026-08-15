@extends('layouts.app')
@section('title', 'Kendaraan')
@section('header', 'Kendaraan')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <form method="GET" class="flex gap-2">
                <input name="q" value="{{ $q }}" placeholder="Cari plat / merk / tipe…" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Cari</button>
            </form>
            <a href="{{ route('vehicles.create') }}" class="btn-primary">+ Kendaraan</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Plat</th><th>Jenis</th><th>Merk/Tipe</th><th>Tahun</th><th>Pemilik</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($vehicles as $v)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-medium">{{ $v->plat }}</td>
                            <td class="capitalize">{{ $v->jenis }}</td>
                            <td>{{ $v->merk }} {{ $v->tipe }}</td>
                            <td>{{ $v->tahun }}</td>
                            <td>{{ $v->customer?->nama }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('vehicles.edit', $v) }}" class="text-brand hover:underline">Edit</a>
                                <form action="{{ route('vehicles.destroy', $v) }}" method="POST" class="inline" data-confirm="Hapus kendaraan {{ $v->plat }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $vehicles->links() }}</div>
    </div>
@endsection
