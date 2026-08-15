@extends('layouts.app')
@section('title', 'Karyawan')
@section('header', 'Karyawan & Gaji')

@section('content')
    <div class="card p-5">
        <div class="flex justify-end mb-4">
            <a href="{{ route('employees.create') }}" class="btn-primary">@include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4']) Karyawan</a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Nama</th><th>Jabatan</th><th class="text-right">Gaji Pokok</th><th class="text-center">Komisi</th><th class="text-right">Gaji Bln Ini</th><th class="text-center">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($employees as $e)
                    <tr class="hover:bg-gray-50">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-brand-light text-brand flex items-center justify-center text-xs font-semibold">{{ strtoupper(\Illuminate\Support\Str::substr($e->nama, 0, 2)) }}</span>
                                <span class="font-medium text-gray-800">{{ $e->nama }}</span>
                            </div>
                        </td>
                        <td class="text-gray-600">{{ $e->jabatan ?? '-' }}</td>
                        <td class="text-right">{{ rupiah($e->gaji_pokok) }}</td>
                        <td class="text-center text-gray-500">{{ $e->komisi_persen ? $e->komisi_persen.'%' : '-' }}</td>
                        <td class="text-right {{ $e->gaji_bulan_ini ? 'text-emerald-600 font-medium' : 'text-gray-300' }}">{{ $e->gaji_bulan_ini ? rupiah($e->gaji_bulan_ini) : 'belum' }}</td>
                        <td class="text-center"><span class="badge {{ $e->aktif ? 'badge-green' : 'badge-gray' }}">{{ $e->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('employees.show', $e) }}" class="text-brand hover:underline">Gaji</a>
                            <a href="{{ route('employees.edit', $e) }}" class="text-gray-600 hover:underline ml-2">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
