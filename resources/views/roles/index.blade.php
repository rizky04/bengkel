@extends('layouts.app')
@section('title', 'Role & Akses')
@section('header', 'Role & Akses')

@section('content')
    <div class="card p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('roles.create') }}" class="btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Role</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Role</th><th>Key</th><th class="text-center">Akses</th><th class="text-center">Pengguna</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($roles as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="font-medium text-gray-800">{{ $r->nama }}</td>
                        <td class="font-mono text-xs text-gray-500">{{ $r->key }}</td>
                        <td class="text-center">
                            @if ($r->is_admin)
                                <span class="badge badge-blue">Akses Penuh</span>
                            @else
                                <span class="badge badge-gray">{{ $r->permissions_count }} menu</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $userCounts[$r->key] ?? 0 }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('roles.edit', $r) }}" class="text-brand hover:underline">Atur Akses</a>
                            @if (! $r->is_admin && ($userCounts[$r->key] ?? 0) === 0)
                                <form action="{{ route('roles.destroy', $r) }}" method="POST" class="inline" data-confirm="Hapus role {{ $r->nama }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
