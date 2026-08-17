@extends('layouts.app')
@section('title', 'Edit Transaksi')
@section('header', 'Edit Transaksi — ' . $trx->no_nota)

@section('content')
    <form method="POST" action="{{ route('transactions.update', $trx) }}" x-data="editor()" class="space-y-4">
        @csrf @method('PUT')

        @if ($modeAjuan)
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                Transaksi ini sudah <strong>lunas</strong>. Perubahan Anda akan <strong>diajukan untuk disetujui admin/owner</strong>, belum langsung diterapkan.
                @if ($pendingAda)<div class="mt-1 text-amber-700">⚠ Sudah ada pengajuan yang menunggu untuk transaksi ini.</div>@endif
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-6 items-start">
            {{-- KIRI: item --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Pencarian item --}}
                <div class="card p-4">
                    <label class="form-label">Tambah Item</label>
                    <div class="relative" @click.outside="cari = ''">
                        <input type="text" x-model="cari" placeholder="Cari sparepart / jasa untuk ditambahkan…"
                               @keydown.enter.prevent="if (hasilCari.length) tambah(hasilCari[0])"
                               class="w-full rounded-lg border-gray-300 text-sm">
                        <div x-show="cari.length" x-cloak class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="r in hasilCari" :key="r.tipe + r.ref_id">
                                <button type="button" @click="tambah(r)" class="w-full text-left px-3 py-2 hover:bg-brand-light text-sm flex justify-between items-center">
                                    <span>
                                        <span class="px-1.5 py-0.5 rounded text-xs mr-2" :class="r.tipe === 'part' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" x-text="r.tipe"></span>
                                        <span x-text="r.nama"></span>
                                        <span class="text-gray-400 text-xs" x-show="r.tipe === 'part'" x-text="` (stok ${r.stok})`"></span>
                                    </span>
                                    <span class="text-gray-500" x-text="rp(r.harga)"></span>
                                </button>
                            </template>
                            <div x-show="!hasilCari.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
                        </div>
                    </div>

                    {{-- Daftar item --}}
                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                                <tr><th class="py-2">Item</th><th class="text-center w-20">Qty</th><th class="text-right w-32">Harga</th><th class="text-right w-28">Diskon</th><th class="text-right w-32">Subtotal</th><th></th></tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="(it, i) in items" :key="i">
                                    <tr>
                                        <td class="py-2">
                                            <span x-text="it.nama"></span>
                                            <span class="text-xs text-amber-600" x-show="it.diretur > 0" x-text="`(diretur ${it.diretur})`"></span>
                                        </td>
                                        <td><input type="number" :min="it.diretur || 1" x-model.number="it.qty" @input="batasi(it)" class="w-full rounded border-gray-300 text-sm text-center"></td>
                                        <td><input type="number" min="0" x-model.number="it.harga" class="w-full rounded border-gray-300 text-sm text-right"></td>
                                        <td><input type="number" min="0" x-model.number="it.diskon" class="w-full rounded border-gray-300 text-sm text-right"></td>
                                        <td class="text-right font-medium" x-text="rp(it.qty * it.harga - (it.diskon||0))"></td>
                                        <td class="text-right"><button type="button" @click="hapus(i)" :disabled="it.diretur > 0" class="text-red-600 disabled:opacity-30" title="hapus">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="!items.length"><td colspan="6" class="py-8 text-center text-gray-400">Tidak ada item.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- hidden submit --}}
                    <template x-for="(it, i) in items" :key="'h'+i">
                        <div>
                            <input type="hidden" :name="`items[${i}][id]`" :value="it.id || ''">
                            <input type="hidden" :name="`items[${i}][tipe]`" :value="it.tipe">
                            <input type="hidden" :name="`items[${i}][ref_id]`" :value="it.ref_id || ''">
                            <input type="hidden" :name="`items[${i}][nama]`" :value="it.nama">
                            <input type="hidden" :name="`items[${i}][qty]`" :value="it.qty">
                            <input type="hidden" :name="`items[${i}][harga]`" :value="it.harga">
                            <input type="hidden" :name="`items[${i}][diskon]`" :value="it.diskon || 0">
                        </div>
                    </template>
                </div>
            </div>

            {{-- KANAN: metadata + ringkasan --}}
            <div class="space-y-4">
                <div class="card p-4 space-y-3">
                    @include('partials.field', ['name' => 'tgl', 'label' => 'Tanggal', 'type' => 'datetime-local', 'value' => $trx->tgl?->format('Y-m-d\TH:i'), 'required' => true])
                    @include('partials.search-select', ['name' => 'platform_id', 'label' => 'Platform', 'value' => $trx->platform_id, 'placeholder' => '— platform —', 'options' => $platforms->pluck('nama', 'id')])
                    @include('partials.search-select', ['name' => 'customer_id', 'label' => 'Pelanggan', 'value' => $trx->customer_id, 'placeholder' => 'Walk-in Customer', 'options' => $customers->pluck('nama', 'id')])
                    @if ($trx->tipe === 'servis')
                        @include('partials.search-select', ['name' => 'vehicle_id', 'label' => 'Kendaraan', 'value' => $trx->vehicle_id, 'placeholder' => '— kendaraan —', 'options' => $vehicles->mapWithKeys(fn($v) => [$v->id => $v->plat.' — '.$v->merk.' '.$v->tipe])])
                        @include('partials.search-select', ['name' => 'mekanik_id', 'label' => 'Mekanik', 'value' => $trx->mekanik_id, 'placeholder' => '— mekanik —', 'options' => $mekaniks->pluck('name', 'id')])
                    @endif
                    <div>
                        <label class="form-label">Diskon Manual (Rp)</label>
                        <input type="number" min="0" name="diskon" x-model.number="diskon" class="form-input text-right">
                    </div>
                    @if ($modeAjuan)
                        <div>
                            <label class="form-label">Alasan Perubahan <span class="text-rose-500">*</span></label>
                            <textarea name="alasan" rows="2" required class="form-input" placeholder="mis. pelanggan menambah ganti oli setelah lunas">{{ old('alasan') }}</textarea>
                        </div>
                    @endif
                </div>

                @if ($trx->tipe === 'servis')
                    <div class="card p-4 space-y-3">
                        @include('partials.field', ['name' => 'keluhan', 'label' => 'Keluhan', 'type' => 'textarea', 'value' => $trx->keluhan])
                        @include('partials.field', ['name' => 'catatan_mekanik', 'label' => 'Catatan Mekanik', 'type' => 'textarea', 'value' => $trx->catatan_mekanik])
                    </div>
                @endif

                <div class="card p-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span x-text="rp(subtotal)"></span></div>
                    <div class="flex justify-between text-rose-600" x-show="diskonTotal > 0"><span>Diskon</span><span x-text="'- ' + rp(diskonTotal)"></span></div>
                    @if ($pajakPersen)
                        <div class="flex justify-between"><span class="text-gray-500">Pajak ({{ $pajakPersen }}%)</span><span x-text="rp(pajak)"></span></div>
                    @endif
                    <div class="flex justify-between text-lg font-bold border-t pt-2"><span>Total</span><span class="text-brand" x-text="rp(total)"></span></div>
                    <p class="text-xs text-gray-400 pt-1">Dibayar Rp {{ number_format($trx->dibayar, 0, ',', '.') }} — bila total naik, status turun ke "selesai".</p>
                </div>

                <div class="flex gap-2">
                    <button class="btn-primary flex-1" :disabled="!items.length">{{ $modeAjuan ? 'Ajukan Perubahan' : 'Simpan Perubahan' }}</button>
                    <a href="{{ route('transactions.show', $trx) }}" class="btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@php
    $itemsAwal = $trx->items->map(fn ($it) => [
        'id' => $it->id, 'tipe' => $it->tipe, 'ref_id' => $it->ref_id, 'nama' => $it->nama,
        'qty' => (int) $it->qty, 'harga' => (float) $it->harga, 'diskon' => (float) $it->diskon, 'diretur' => $it->qtyDiretur(),
    ])->values();
@endphp
@push('scripts')
<script>
    const PARTS = @json($parts);
    const SERVICES = @json($services);
    const ITEMS_AWAL = @json($itemsAwal);
    const PAJAK = {{ (float) $pajakPersen }};

    function editor() {
        return {
            items: JSON.parse(JSON.stringify(ITEMS_AWAL)),
            diskon: {{ (float) $trx->diskon }},
            cari: '',
            get hasilCari() {
                const q = this.cari.toLowerCase().trim();
                if (!q) return [];
                const parts = PARTS.filter(p => (p.nama + ' ' + p.kode).toLowerCase().includes(q))
                    .map(p => ({ tipe: 'part', ref_id: p.id, nama: p.nama, harga: Number(p.harga_jual), stok: p.stok }));
                const svc = SERVICES.filter(s => s.nama.toLowerCase().includes(q))
                    .map(s => ({ tipe: 'jasa', ref_id: s.id, nama: s.nama, harga: Number(s.tarif), stok: null }));
                return [...parts, ...svc].slice(0, 10);
            },
            tambah(r) {
                const ada = this.items.find(it => it.tipe === r.tipe && it.ref_id === r.ref_id);
                if (ada) ada.qty++;
                else this.items.push({ id: null, tipe: r.tipe, ref_id: r.ref_id, nama: r.nama, qty: 1, harga: r.harga, diskon: 0, diretur: 0, stok: r.stok });
                this.cari = '';
            },
            batasi(it) { if (it.qty < (it.diretur || 1)) it.qty = it.diretur || 1; },
            hapus(i) { if (!this.items[i].diretur) this.items.splice(i, 1); },
            get subtotal() { return this.items.reduce((s, it) => s + (it.qty * it.harga - (it.diskon || 0)), 0); },
            get diskonTotal() { return Math.min(this.subtotal, this.diskon || 0); },
            get pajak() { return PAJAK ? Math.round((this.subtotal - this.diskonTotal) * PAJAK / 100) : 0; },
            get total() { return this.subtotal - this.diskonTotal + this.pajak; },
            rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
