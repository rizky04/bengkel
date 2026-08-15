@extends('layouts.app')
@section('title', 'Shift Kasir')
@section('header', 'Shift Kasir')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Panel shift aktif / buka --}}
        <div class="card p-5">
            @if ($aktif)
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-semibold text-gray-700">Shift Sedang Berjalan</span>
                </div>
                <dl class="text-sm space-y-1 mb-4">
                    <div class="flex justify-between"><dt class="text-gray-500">Dibuka</dt><dd>{{ $aktif->buka_at->format('d/m/Y H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Kas Awal</dt><dd>{{ rupiah($aktif->kas_awal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Penjualan Tunai</dt><dd class="text-emerald-600">{{ rupiah($aktif->penjualanTunai()) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Pengeluaran Tunai</dt><dd class="text-rose-600">{{ rupiah($aktif->pengeluaranTunai()) }}</dd></div>
                    <div class="flex justify-between border-t pt-1 font-semibold"><dt>Kas Seharusnya</dt><dd class="text-brand">{{ rupiah($aktif->kasSeharusnya()) }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('shifts.close', $aktif) }}" class="space-y-3 border-t pt-3"
                      x-data="{ fisik: {{ (int) $aktif->kasSeharusnya() }}, seharusnya: {{ (int) $aktif->kasSeharusnya() }},
                                get selisih(){ return this.fisik - this.seharusnya } }"
                      data-confirm="Tutup shift sekarang?">
                    @csrf @method('PATCH')
                    <div>
                        <label class="form-label">Kas Fisik di Laci</label>
                        <input type="number" name="kas_akhir_fisik" x-model.number="fisik" class="form-input text-right" required>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Selisih</span>
                        <span class="font-semibold" :class="selisih < 0 ? 'text-rose-600' : selisih > 0 ? 'text-amber-600' : 'text-emerald-600'"
                              x-text="(selisih>0?'+':'') + 'Rp ' + selisih.toLocaleString('id-ID')"></span>
                    </div>
                    <div>
                        <label class="form-label">Catatan (opsional)</label>
                        <input name="catatan" class="form-input" placeholder="mis. selisih karena kembalian">
                    </div>
                    <button class="btn-danger w-full">Tutup Shift</button>
                </form>
            @else
                <div class="font-semibold text-gray-700 mb-3">Buka Shift</div>
                <p class="text-sm text-gray-500 mb-4">Mulai sesi kasir dengan mencatat kas awal (uang di laci saat mulai).</p>
                <form method="POST" action="{{ route('shifts.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Kas Awal</label>
                        <input type="number" name="kas_awal" value="0" class="form-input text-right" required>
                    </div>
                    <button class="btn-primary w-full">Buka Shift</button>
                </form>
            @endif
        </div>

        {{-- Riwayat --}}
        <div class="card lg:col-span-2">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Riwayat Shift</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Kasir</th><th>Buka</th><th>Tutup</th><th class="text-right">Kas Awal</th><th class="text-right">Kas Fisik</th><th class="text-right">Selisih</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($shifts as $s)
                        <tr class="hover:bg-gray-50">
                            <td class="font-medium">{{ $s->user?->name }}</td>
                            <td class="text-gray-600 whitespace-nowrap">{{ $s->buka_at?->format('d/m H:i') }}</td>
                            <td class="text-gray-600 whitespace-nowrap">{{ $s->tutup_at?->format('d/m H:i') ?? '—' }}</td>
                            <td class="text-right">{{ rupiah($s->kas_awal) }}</td>
                            <td class="text-right">{{ $s->kas_akhir_fisik !== null ? rupiah($s->kas_akhir_fisik) : '—' }}</td>
                            <td class="text-right {{ $s->status === 'tutup' ? ($s->selisih() < 0 ? 'text-rose-600' : ($s->selisih() > 0 ? 'text-amber-600' : 'text-emerald-600')) : 'text-gray-300' }}">
                                {{ $s->status === 'tutup' ? rupiah($s->selisih()) : 'buka' }}
                            </td>
                            <td class="text-right"><a href="{{ route('shifts.show', $s) }}" class="text-brand hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada shift.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $shifts->links() }}</div>
        </div>
    </div>
@endsection
