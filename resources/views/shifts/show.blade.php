@extends('layouts.app')
@section('title', 'Detail Shift')
@section('header', 'Detail Shift Kasir')

@section('content')
    <div class="card max-w-lg">
        <div class="px-5 py-3 border-b flex justify-between items-center">
            <span class="font-semibold text-gray-700">{{ $shift->user?->name }}</span>
            <span class="badge {{ $shift->status === 'tutup' ? 'badge-gray' : 'badge-green' }}">{{ $shift->status === 'tutup' ? 'Ditutup' : 'Berjalan' }}</span>
        </div>
        <div class="p-5 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Dibuka</span><span>{{ $shift->buka_at?->format('d/m/Y H:i') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Ditutup</span><span>{{ $shift->tutup_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Jumlah Transaksi</span><span>{{ $shift->jumlahTransaksi() }}</span></div>
            <div class="border-t pt-2 mt-2"></div>
            <div class="flex justify-between"><span class="text-gray-500">Kas Awal</span><span>{{ rupiah($shift->kas_awal) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Penjualan Tunai</span><span class="text-emerald-600">+ {{ rupiah($shift->penjualanTunai()) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Pengeluaran Tunai</span><span class="text-rose-600">− {{ rupiah($shift->pengeluaranTunai()) }}</span></div>
            <div class="flex justify-between border-t pt-2 font-semibold"><span>Kas Seharusnya</span><span class="text-brand">{{ rupiah($shift->kasSeharusnya()) }}</span></div>
            @if ($shift->status === 'tutup')
                <div class="flex justify-between"><span class="text-gray-500">Kas Fisik</span><span>{{ rupiah($shift->kas_akhir_fisik) }}</span></div>
                <div class="flex justify-between text-lg font-bold {{ $shift->selisih() < 0 ? 'text-rose-600' : ($shift->selisih() > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                    <span>Selisih</span><span>{{ rupiah($shift->selisih()) }}</span>
                </div>
                @if ($shift->catatan)<div class="text-gray-500 pt-2">Catatan: {{ $shift->catatan }}</div>@endif
            @endif
        </div>
    </div>
    <a href="{{ route('shifts.index') }}" class="inline-block mt-4 btn-secondary">← Kembali</a>
@endsection
