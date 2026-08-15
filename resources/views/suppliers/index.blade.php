@extends('layouts.app')
@section('title', 'Supplier')
@section('header', 'Supplier')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <form method="GET" class="flex gap-2">
                <input name="q" value="{{ $q }}" placeholder="Cari supplier…" class="rounded-lg border-gray-300 text-sm">
                <button class="btn-secondary btn-sm">Cari</button>
            </form>
            <a href="{{ route('suppliers.create') }}" class="btn-primary">+ Supplier</a>
        </div>

        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th class="py-2">Nama</th><th>HP</th><th>Alamat</th><th></th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($suppliers as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 font-medium">{{ $s->nama }}</td>
                        <td>{{ $s->hp }}</td>
                        <td class="text-gray-500">{{ Str::limit($s->alamat, 40) }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('suppliers.edit', $s) }}" class="text-brand hover:underline">Edit</a>
                            <form action="{{ route('suppliers.destroy', $s) }}" method="POST" class="inline" data-confirm="Hapus {{ $s->nama }}?">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline ml-2">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $suppliers->links() }}</div>
    </div>
@endsection
