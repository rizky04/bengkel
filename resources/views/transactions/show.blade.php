@extends('layouts.app')
@section('title', 'Transaksi ' . $trx->no_nota)
@section('header', 'Transaksi ' . $trx->no_nota)

@section('content')
    @php
        $badge = [
            'antri' => 'bg-gray-100 text-gray-700', 'dikerjakan' => 'bg-blue-100 text-blue-700',
            'selesai' => 'bg-amber-100 text-amber-700', 'lunas' => 'bg-emerald-100 text-emerald-700',
            'batal' => 'bg-rose-100 text-rose-700',
        ][$trx->status] ?? 'bg-gray-100';
    @endphp

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- Detail item --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="card">
                <div class="px-5 py-3 border-b flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-700 capitalize">{{ $trx->tipe }}</span>
                        <span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ ucfirst($trx->status) }}</span>
                    </div>
                    <a href="{{ route('transactions.nota', $trx) }}" target="_blank" class="btn-secondary btn-sm">@include('partials.icon', ['name' => 'printer', 'class' => 'w-4 h-4']) Nota</a>
                </div>
                <div class="p-5">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                            <tr><th class="py-2">Item</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Diskon</th><th class="text-right">Subtotal</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($trx->items as $it)
                                <tr>
                                    <td class="py-2">
                                        <span class="px-1.5 py-0.5 rounded text-xs mr-1 {{ $it->tipe === 'part' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $it->tipe }}</span>
                                        {{ $it->nama }}
                                    </td>
                                    <td class="text-center">{{ $it->qty }}</td>
                                    <td class="text-right">{{ rupiah($it->harga) }}</td>
                                    <td class="text-right">{{ $it->diskon > 0 ? rupiah($it->diskon) : '-' }}</td>
                                    <td class="text-right font-medium">{{ rupiah($it->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ rupiah($trx->subtotal) }}</span></div>
                        @if ($trx->diskon > 0)
                            <div class="flex justify-between text-rose-600"><span>Diskon @if($trx->promo)({{ $trx->promo->nama }})@endif</span><span>- {{ rupiah($trx->diskon) }}</span></div>
                        @endif
                        @if ($trx->pajak > 0)
                            <div class="flex justify-between"><span class="text-gray-500">Pajak</span><span>{{ rupiah($trx->pajak) }}</span></div>
                        @endif
                        <div class="flex justify-between text-lg font-bold border-t pt-1"><span>Total</span><span class="text-brand">{{ rupiah($trx->total) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Dibayar</span><span>{{ rupiah($trx->dibayar) }}</span></div>
                        <div class="flex justify-between font-semibold {{ $trx->sisa > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            <span>{{ $trx->sisa > 0 ? 'Sisa' : 'Lunas' }}</span><span>{{ rupiah(max(0, $trx->sisa)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Riwayat retur --}}
            @if ($trx->returns->count())
                <div class="card">
                    <div class="px-5 py-3 border-b font-semibold text-gray-700">Riwayat Retur</div>
                    <div class="p-5 space-y-1">
                        @foreach ($trx->returns as $r)
                            <div class="flex justify-between text-sm">
                                <a href="{{ route('returns.show', $r) }}" class="text-brand hover:underline">{{ $r->no }}</a>
                                <span class="text-gray-500">{{ $r->tgl?->format('d/m/Y') }} · {{ $r->alasan }}</span>
                                <span class="text-rose-600 font-medium">− {{ rupiah($r->total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Riwayat pembayaran --}}
            @if ($trx->payments->count())
                <div class="card">
                    <div class="px-5 py-3 border-b font-semibold text-gray-700">Riwayat Pembayaran</div>
                    <div class="p-5">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y">
                                @foreach ($trx->payments as $pay)
                                    <tr>
                                        <td class="py-2">{{ $pay->tgl_bayar?->format('d/m/Y H:i') }}</td>
                                        <td class="capitalize">{{ $pay->metode }}</td>
                                        <td class="text-right font-medium">{{ rupiah($pay->jumlah) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Panel aksi --}}
        <div class="space-y-4">
            <div class="card p-5 text-sm space-y-2">
                <div><span class="text-gray-500">Tanggal:</span> {{ $trx->tgl?->format('d/m/Y H:i') }}</div>
                <div><span class="text-gray-500">Kasir:</span> {{ $trx->kasir?->name }}</div>
                <div><span class="text-gray-500">Platform:</span> {{ $trx->platform?->nama ?? '-' }}</div>
                @if ($trx->tipe === 'servis')
                    <div><span class="text-gray-500">Pelanggan:</span> {{ $trx->customer?->nama ?? '-' }}</div>
                    <div><span class="text-gray-500">Kendaraan:</span> {{ $trx->vehicle?->plat ?? '-' }}</div>
                    <div><span class="text-gray-500">Mekanik:</span> {{ $trx->mekanik?->name ?? '-' }}</div>
                    @if ($trx->keluhan)<div><span class="text-gray-500">Keluhan:</span> {{ $trx->keluhan }}</div>@endif
                @endif
            </div>

            @if ($trx->status !== 'batal')
                {{-- Ubah status kerja (servis) --}}
                @if ($trx->tipe === 'servis' && $trx->status !== 'lunas')
                    <div class="card p-5">
                        <div class="text-sm font-medium text-gray-700 mb-2">Status Pengerjaan</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['antri' => 'Antri', 'dikerjakan' => 'Dikerjakan', 'selesai' => 'Selesai'] as $s => $label)
                                <form method="POST" action="{{ route('transactions.status', $trx) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $s }}">
                                    <button class="px-3 py-1.5 rounded-lg text-sm border {{ $trx->status === $s ? 'bg-brand text-white border-brand' : 'hover:bg-gray-50' }}">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tambah pembayaran --}}
                @if ($trx->sisa > 0)
                    <div class="card p-5">
                        <div class="text-sm font-medium text-gray-700 mb-2">Tambah Pembayaran</div>
                        <form method="POST" action="{{ route('transactions.payment', $trx) }}" class="space-y-2">
                            @csrf
                            <select name="metode" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach (['tunai','transfer','qris','kartu'] as $m)
                                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="jumlah" min="1" value="{{ (int) $trx->sisa }}" class="w-full rounded-lg border-gray-300 text-sm text-right">
                            <button class="w-full py-2 bg-emerald-600 text-white rounded-lg text-sm">Catat Pembayaran</button>
                        </form>
                    </div>
                @endif

                @php
                    $adminEdit = auth()->user()->canAccess('transactions_edit');
                    $bisaAksi = $adminEdit || auth()->user()->canAccess('pos'); // admin langsung, kasir mengajukan
                    $ajukanMode = $trx->terkunci() && ! $adminEdit; // terkunci & bukan admin → jadi pengajuan
                @endphp

                {{-- Pengajuan menunggu --}}
                @if ($pending)
                    <div class="card p-4 bg-amber-50 border-amber-200 text-sm">
                        <div class="font-medium text-amber-800">⏳ Menunggu persetujuan admin</div>
                        <div class="text-amber-700 mt-1">
                            {{ $pending->jenis === 'batal' ? 'Pembatalan' : 'Perubahan item' }}
                            diajukan {{ $pending->pengaju?->name }} — “{{ $pending->alasan }}”
                        </div>
                    </div>
                @endif

                {{-- Edit & Retur --}}
                <div class="flex gap-2">
                    @if ($bisaAksi)
                        <a href="{{ route('transactions.edit', $trx) }}" class="btn-secondary btn-sm flex-1">✎ {{ $ajukanMode ? 'Ajukan Edit' : 'Edit' }}</a>
                    @endif
                    @if (auth()->user()->canAccess('returns') && $trx->items->contains(fn ($it) => $it->sisaRetur() > 0))
                        <a href="{{ route('returns.create', ['transaction' => $trx->id]) }}" class="btn-secondary btn-sm flex-1">↩ Retur</a>
                    @endif
                </div>

                {{-- Batal / Ajukan pembatalan --}}
                @if ($bisaAksi)
                <div class="card p-4" x-data="{ open: false }">
                    <button type="button" @click="open = true" x-show="!open"
                            class="w-full py-2 text-rose-600 text-sm hover:underline">{{ $ajukanMode ? 'Ajukan Pembatalan' : 'Batalkan Transaksi' }}</button>
                    <form x-show="open" x-cloak method="POST" action="{{ route('transactions.cancel', $trx) }}"
                          data-confirm="{{ $ajukanMode ? 'Ajukan pembatalan transaksi ' . $trx->no_nota . ' ke admin?' : 'Batalkan transaksi ' . $trx->no_nota . '? Stok dikembalikan.' }}" class="space-y-2">
                        @csrf @method('DELETE')
                        <input name="alasan_batal" required placeholder="Alasan pembatalan…" class="form-input">
                        <div class="flex gap-2">
                            <button class="btn-danger btn-sm flex-1">{{ $ajukanMode ? 'Ya, Ajukan' : 'Ya, Batalkan' }}</button>
                            <button type="button" @click="open = false" class="btn-secondary btn-sm">Batal</button>
                        </div>
                    </form>
                </div>
                @endif
            @else
                <div class="card p-4 bg-rose-50 border-rose-200 text-sm">
                    <span class="text-rose-700 font-medium">Dibatalkan.</span>
                    @if ($trx->alasan_batal)<div class="text-rose-600 mt-1">Alasan: {{ $trx->alasan_batal }}</div>@endif
                </div>
            @endif

            <a href="{{ route('transactions.index') }}" class="block text-center py-2 bg-gray-100 rounded-lg text-sm">← Daftar Transaksi</a>
        </div>
    </div>
@endsection
