@extends('layouts.app')
@section('title', 'Pengingat Servis')
@section('header', 'Pengingat Servis Berkala')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="text-sm text-gray-500">
                Kendaraan yang <strong>sudah lewat</strong> atau <strong>akan jatuh tempo</strong> servis (interval default {{ $defaultInterval }} hari, bisa diatur per kendaraan).
            </p>
            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tampilkan s/d (hari ke depan)</label>
                    <input type="number" name="lookahead" value="{{ $lookahead }}" min="0" class="form-input w-28" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Kendaraan</th><th>Pelanggan</th><th>Servis Terakhir</th><th>Jatuh Tempo</th><th class="text-center">Status</th><th></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($vehicles as $v)
                    @php
                        $lewat = $v->jatuh_tempo->isPast();
                        $selisih = now()->startOfDay()->diffInDays($v->jatuh_tempo, false);
                        $wa = $v->customer?->hp ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $v->customer->hp)) : null;
                        $pesan = rawurlencode("Halo {$v->customer?->nama}, kendaraan {$v->plat} sudah waktunya servis berkala di " . \App\Models\Setting::get('nama_bengkel', 'bengkel kami') . ". Terima kasih 🙏");
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="font-medium text-gray-800">{{ $v->plat }} <span class="text-gray-400 text-xs">{{ $v->merk }} {{ $v->tipe }}</span></td>
                        <td>{{ $v->customer?->nama ?? '-' }}<div class="text-xs text-gray-400">{{ $v->customer?->hp }}</div></td>
                        <td class="text-gray-600">{{ \Illuminate\Support\Carbon::parse($v->servis_terakhir)->format('d/m/Y') }}</td>
                        <td class="text-gray-600">{{ $v->jatuh_tempo->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @if ($lewat)
                                <span class="badge badge-red">lewat {{ abs($selisih) }} hari</span>
                            @else
                                <span class="badge badge-amber">{{ $selisih }} hari lagi</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($wa)
                                <a href="https://wa.me/{{ $wa }}?text={{ $pesan }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-green-600 hover:underline">📱 WhatsApp</a>
                            @else
                                <span class="text-gray-300 text-xs">tanpa HP</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-400">Tidak ada kendaraan yang perlu diingatkan 👍</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
