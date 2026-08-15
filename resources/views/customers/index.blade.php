@extends('layouts.app')
@section('title', 'Pelanggan')
@section('header', 'Pelanggan')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <form method="GET" class="flex gap-2">
                <input name="q" value="{{ $q }}" placeholder="Cari nama / HP…" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Cari</button>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('customers.export') }}" class="btn-secondary btn-sm">⬇ Export</a>
                <a href="{{ route('customers.create') }}" class="btn-primary">+ Pelanggan</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Nama</th><th>HP</th><th>Alamat</th><th class="text-center">Kendaraan</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($customers as $c)
                        <tr class="hover:bg-gray-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-brand-light text-brand flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(\Illuminate\Support\Str::substr($c->nama, 0, 2)) }}
                                    </span>
                                    <span class="font-medium text-gray-800">{{ $c->nama }}</span>
                                </div>
                            </td>
                            <td class="text-gray-600">{{ $c->hp }}</td>
                            <td class="text-gray-500">{{ Str::limit($c->alamat, 40) }}</td>
                            <td class="text-center">{{ $c->vehicles_count }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('customers.show', $c) }}" class="text-gray-600 hover:underline">Detail</a>
                                <a href="{{ route('customers.edit', $c) }}" class="text-brand hover:underline ml-2">Edit</a>
                                <form action="{{ route('customers.destroy', $c) }}" method="POST" class="inline"
                                      data-confirm="Hapus pelanggan {{ $c->nama }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>
@endsection
