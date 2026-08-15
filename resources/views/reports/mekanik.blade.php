@extends('layouts.app')
@section('title', 'Produktivitas Mekanik')
@section('header', 'Produktivitas Mekanik')

@section('content')
    @include('partials.date-filter')

    <div class="card">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Mekanik</th><th class="text-center">Jumlah Order</th><th class="text-right">Nilai Jasa Dikerjakan</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($data as $row)
                    <tr class="hover:bg-gray-50">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-brand-light text-brand flex items-center justify-center text-xs font-semibold">{{ strtoupper(\Illuminate\Support\Str::substr($row->name, 0, 2)) }}</span>
                                <span class="font-medium text-gray-800">{{ $row->name }}</span>
                            </div>
                        </td>
                        <td class="text-center font-medium">{{ $row->jml_order }}</td>
                        <td class="text-right font-semibold text-brand">{{ rupiah($row->nilai_jasa) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-gray-400">Belum ada order servis di periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
