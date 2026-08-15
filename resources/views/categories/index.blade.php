@extends('layouts.app')
@section('title', 'Kategori')
@section('header', 'Kategori')

@section('content')
    <div class="flex justify-end mb-4">
        <a href="{{ route('categories.create') }}" class="btn-primary">+ Kategori</a>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @foreach (['part' => 'Kategori Sparepart', 'jasa' => 'Kategori Jasa'] as $tipe => $judul)
            <div class="card">
                <div class="px-5 py-3 border-b font-semibold text-gray-700">{{ $judul }}</div>
                <div class="p-5">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y">
                            @forelse ($categories[$tipe] ?? [] as $c)
                                <tr>
                                    <td class="py-2">{{ $c->nama }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('categories.edit', $c) }}" class="text-brand hover:underline">Edit</a>
                                        <form action="{{ route('categories.destroy', $c) }}" method="POST" class="inline" data-confirm="Hapus {{ $c->nama }}?">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="py-4 text-center text-gray-400">Belum ada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
