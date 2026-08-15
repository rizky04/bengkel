@extends('layouts.app')
@section('title', 'Gaji ' . $employee->nama)
@section('header', 'Penggajian — ' . $employee->nama)

@section('content')
    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Bayar gaji --}}
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-3">Bayar Gaji</div>
            @if ($sudahDibayar && $sudahDibayar->periode === $periode)
                <p class="text-sm text-amber-600 mb-3">Gaji periode {{ $periode }} sudah dibayar ({{ rupiah($sudahDibayar->total_dibayar) }}).</p>
            @endif
            <form method="POST" action="{{ route('employees.salary.store', $employee) }}" class="space-y-3"
                  x-data="{ pokok: {{ $employee->gaji_pokok }}, bonus: 0, komisi: {{ $komisiUsulan }}, potongan: 0,
                            get total(){ return this.pokok + this.bonus + this.komisi - this.potongan } }"
                  data-confirm="Bayar & catat gaji sebagai pengeluaran?">
                @csrf
                <div>
                    <label class="form-label">Periode</label>
                    <input type="month" name="periode" value="{{ $periode }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Gaji Pokok</label>
                    <input type="number" name="gaji_pokok" x-model.number="pokok" class="form-input text-right" required>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="form-label">Bonus</label><input type="number" name="bonus" x-model.number="bonus" class="form-input text-right"></div>
                    <div><label class="form-label">Potongan</label><input type="number" name="potongan" x-model.number="potongan" class="form-input text-right"></div>
                </div>
                <div>
                    <label class="form-label">Komisi @if($komisiUsulan > 0)<span class="text-brand text-xs">(usulan {{ rupiah($komisiUsulan) }})</span>@endif</label>
                    <input type="number" name="komisi" x-model.number="komisi" class="form-input text-right">
                </div>
                <div>
                    <label class="form-label">Tanggal Bayar</label>
                    <input type="date" name="tgl_bayar" value="{{ now()->toDateString() }}" class="form-input" required>
                </div>
                <div class="flex justify-between items-center border-t pt-3">
                    <span class="text-sm text-gray-500">Total Dibayar</span>
                    <span class="text-lg font-bold text-brand" x-text="'Rp ' + total.toLocaleString('id-ID')"></span>
                </div>
                <button class="btn-primary w-full">Bayar Gaji</button>
            </form>
        </div>

        {{-- Riwayat --}}
        <div class="card lg:col-span-2">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Riwayat Penggajian</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Periode</th><th class="text-right">Pokok</th><th class="text-right">Bonus</th><th class="text-right">Komisi</th><th class="text-right">Potongan</th><th class="text-right">Total</th><th>Dibayar</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employee->salaries as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="font-medium">{{ $s->periode }}</td>
                            <td class="text-right">{{ rupiah($s->gaji_pokok) }}</td>
                            <td class="text-right">{{ rupiah($s->bonus) }}</td>
                            <td class="text-right">{{ rupiah($s->komisi) }}</td>
                            <td class="text-right text-rose-600">{{ $s->potongan > 0 ? '-'.rupiah($s->potongan) : '-' }}</td>
                            <td class="text-right font-semibold">{{ rupiah($s->total_dibayar) }}</td>
                            <td class="text-gray-500">{{ $s->tgl_bayar?->format('d/m/Y') }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('employees.salary.destroy', [$employee, $s]) }}" data-confirm="Batalkan pembayaran gaji {{ $s->periode }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Batal</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-gray-400">Belum ada riwayat gaji.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('employees.index') }}" class="inline-block mt-4 btn-secondary">← Daftar Karyawan</a>
@endsection
