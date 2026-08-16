@extends('layouts.app')
@section('title', 'Persetujuan')
@section('header', 'Persetujuan Perubahan')

@section('content')
    @php
        $statusBadge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-emerald-100 text-emerald-700', 'ditolak' => 'bg-rose-100 text-rose-700'];
    @endphp

    {{-- Tab status --}}
    <div class="flex gap-2 mb-4">
        @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'ditolak' => 'Ditolak'] as $k => $lbl)
            <a href="{{ route('approvals.index', ['status' => $k]) }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ $status === $k ? 'bg-brand text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $lbl }}@if ($k === 'pending' && $jmlPending) <span class="ml-1">({{ $jmlPending }})</span>@endif
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($requests as $r)
            <div class="card p-4" x-data="{ tolak: false }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $r->jenis === 'batal' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $r->jenis === 'batal' ? 'Pembatalan' : 'Edit item' }}
                            </span>
                            <a href="{{ route('transactions.show', $r->transaction_id) }}" class="font-mono text-sm text-brand hover:underline">{{ $r->transaction?->no_nota ?? '—' }}</a>
                            <span class="badge {{ $statusBadge[$r->status] ?? 'bg-gray-100' }}">{{ ucfirst($r->status) }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Alasan: <span class="text-gray-800">{{ $r->alasan }}</span></p>
                        <p class="text-xs text-gray-400 mt-0.5">Diajukan {{ $r->pengaju?->name }} • {{ $r->created_at?->format('d/m/Y H:i') }}
                            @if ($r->decided_at) • diputus {{ $r->penyetuju?->name }} {{ $r->decided_at->format('d/m/Y H:i') }}@endif
                        </p>
                        @if ($r->catatan)<p class="text-xs text-rose-600 mt-0.5">Catatan: {{ $r->catatan }}</p>@endif
                    </div>

                    @if ($r->status === 'pending')
                        <div class="flex gap-2 shrink-0">
                            <form method="POST" action="{{ route('approvals.approve', $r) }}" data-confirm="Setujui & terapkan perubahan ini?">
                                @csrf
                                <button class="btn-primary btn-sm">✓ Setujui</button>
                            </form>
                            <button type="button" @click="tolak = !tolak" class="btn-secondary btn-sm">✕ Tolak</button>
                        </div>
                    @endif
                </div>

                {{-- Preview usulan item (untuk edit) --}}
                @if ($r->jenis === 'edit' && is_array($r->payload['items'] ?? null))
                    <div class="mt-3 border-t pt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-gray-400 text-xs uppercase">
                                <tr><th class="py-1">Usulan Item</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($r->payload['items'] as $it)
                                    <tr>
                                        <td class="py-1">{{ $it['nama'] }}</td>
                                        <td class="text-center">{{ $it['qty'] }}</td>
                                        <td class="text-right">{{ rupiah($it['harga']) }}</td>
                                        <td class="text-right">{{ rupiah($it['qty'] * $it['harga'] - ($it['diskon'] ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Form tolak --}}
                <form x-show="tolak" x-cloak method="POST" action="{{ route('approvals.reject', $r) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input name="catatan" required placeholder="Alasan penolakan…" class="form-input flex-1">
                    <button class="btn-danger btn-sm">Tolak</button>
                </form>
            </div>
        @empty
            <div class="card p-8 text-center text-gray-400">Tidak ada pengajuan {{ $status === 'pending' ? 'yang menunggu' : $status }}.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
