@extends('layouts.app')
@section('title', 'Pengguna')
@section('header', 'Pengguna')

@section('content')
    @php $roleBadge = ['admin' => 'badge-blue', 'kasir' => 'badge-green', 'mekanik' => 'badge-amber']; @endphp
    <div class="card p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('users.create') }}" class="btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Pengguna</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Nama</th><th>Email</th><th class="text-center">Role</th><th class="text-center">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-brand-light text-brand flex items-center justify-center text-xs font-semibold">{{ strtoupper(\Illuminate\Support\Str::substr($u->name, 0, 2)) }}</span>
                                <span class="font-medium text-gray-800">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="text-gray-600">{{ $u->email }}</td>
                        <td class="text-center"><span class="badge {{ $roleBadge[$u->role] ?? 'badge-gray' }} capitalize">{{ $u->role }}</span></td>
                        <td class="text-center"><span class="badge {{ $u->aktif ? 'badge-green' : 'badge-gray' }}">{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('users.edit', $u) }}" class="text-brand hover:underline">Edit</a>
                            @if ($u->id !== auth()->id())
                                <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline" data-confirm="Hapus pengguna {{ $u->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-400">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
@endsection
