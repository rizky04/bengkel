@extends('layouts.app')
@section('title', 'Promo')
@section('header', 'Promo & Diskon')

@section('content')
    <div class="card p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('promos.create') }}" class="btn-primary">+ Promo</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Nama</th><th>Kode</th><th>Jenis</th><th class="text-right">Nilai</th><th>Periode</th><th class="text-center">Kuota</th><th class="text-center">Status</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($promos as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-medium">{{ $p->nama }}</td>
                            <td class="font-mono text-xs">{{ $p->kode ?: '-' }}</td>
                            <td>{{ str_replace('_', ' ', $p->jenis) }}</td>
                            <td class="text-right">{{ $p->jenis === 'persen' ? $p->nilai . '%' : rupiah($p->nilai) }}</td>
                            <td class="text-gray-500 text-xs">
                                {{ $p->mulai?->format('d/m/y') ?? '∞' }} – {{ $p->selesai?->format('d/m/y') ?? '∞' }}
                            </td>
                            <td class="text-center">{{ $p->kuota ? $p->terpakai . '/' . $p->kuota : '∞' }}</td>
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded text-xs {{ $p->aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('promos.edit', $p) }}" class="text-brand hover:underline">Edit</a>
                                <form action="{{ route('promos.destroy', $p) }}" method="POST" class="inline" data-confirm="Hapus promo {{ $p->nama }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-gray-400">Belum ada promo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $promos->links() }}</div>
    </div>
@endsection
