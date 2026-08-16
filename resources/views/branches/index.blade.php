@extends('layouts.app')
@section('title', 'Cabang')
@section('header', 'Cabang')

@section('content')
    <div class="card p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('branches.create') }}" class="btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Cabang</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Nama</th><th>Alamat</th><th>HP</th><th class="text-center">Pengguna</th><th class="text-center">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($branches as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="font-medium text-gray-800">{{ $b->nama }} @if(current_branch() == $b->id)<span class="badge badge-blue ml-1">aktif</span>@endif</td>
                        <td class="text-gray-600">{{ $b->alamat ?? '-' }}</td>
                        <td class="text-gray-600">{{ $b->hp ?? '-' }}</td>
                        <td class="text-center">{{ $b->users_count }}</td>
                        <td class="text-center"><span class="badge {{ $b->aktif ? 'badge-green' : 'badge-gray' }}">{{ $b->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('branches.edit', $b) }}" class="text-brand hover:underline">Edit</a>
                            @if ($branches->count() > 1)
                                <form action="{{ route('branches.destroy', $b) }}" method="POST" class="inline" data-confirm="Hapus cabang {{ $b->nama }}? Data operasional cabang ini ikut terhapus.">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-400">Belum ada cabang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
