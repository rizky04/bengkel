@extends('layouts.app')
@section('title', 'Konsolidasi')
@section('header', 'Laporan Konsolidasi (Semua Cabang)')

@section('content')
    @include('partials.date-filter')

    @php
        $tPend = $rows->sum('pendapatan'); $tHpp = $rows->sum('hpp');
        $tKotor = $rows->sum('laba_kotor'); $tPeng = $rows->sum('pengeluaran'); $tBersih = $rows->sum('laba_bersih');
    @endphp

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Pendapatan</div><div class="text-2xl font-bold text-brand">{{ rupiah($tPend) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Pengeluaran</div><div class="text-2xl font-bold text-rose-600">{{ rupiah($tPeng) }}</div></div>
        <div class="card p-5"><div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Laba Bersih Gabungan</div><div class="text-2xl font-bold {{ $tBersih < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ rupiah($tBersih) }}</div></div>
    </div>

    <div class="card">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Cabang</th><th class="text-right">Pendapatan</th><th class="text-right">HPP</th><th class="text-right">Laba Kotor</th><th class="text-right">Pengeluaran</th><th class="text-right">Laba Bersih</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="font-medium text-gray-800">{{ $r->nama }}</td>
                        <td class="text-right">{{ rupiah($r->pendapatan) }}</td>
                        <td class="text-right text-gray-500">{{ rupiah($r->hpp) }}</td>
                        <td class="text-right">{{ rupiah($r->laba_kotor) }}</td>
                        <td class="text-right text-rose-600">{{ rupiah($r->pengeluaran) }}</td>
                        <td class="text-right font-semibold {{ $r->laba_bersih < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ rupiah($r->laba_bersih) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-400">Belum ada cabang.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-300 font-bold">
                    <td class="px-4 py-3">TOTAL</td>
                    <td class="px-4 py-3 text-right">{{ rupiah($tPend) }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ rupiah($tHpp) }}</td>
                    <td class="px-4 py-3 text-right">{{ rupiah($tKotor) }}</td>
                    <td class="px-4 py-3 text-right text-rose-600">{{ rupiah($tPeng) }}</td>
                    <td class="px-4 py-3 text-right {{ $tBersih < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ rupiah($tBersih) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
