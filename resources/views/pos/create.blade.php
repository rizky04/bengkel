@extends('layouts.app')
@section('title', 'Kasir / POS')
@section('header', 'Kasir / POS')

@section('content')
<form method="POST" action="{{ route('pos.store') }}" x-data="pos()" @submit="return siap()">
    @csrf

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        {{-- KIRI: jenis + pencarian + keranjang --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="card p-4">
                <div class="flex gap-2">
                    <label class="flex-1">
                        <input type="radio" name="tipe" value="penjualan" x-model="tipe" class="peer sr-only">
                        <div class="flex items-center justify-center gap-2 py-2 rounded-lg border cursor-pointer text-sm font-medium text-gray-600 peer-checked:bg-brand peer-checked:text-white peer-checked:border-brand">
                            @include('partials.icon', ['name' => 'cart']) Penjualan Langsung
                        </div>
                    </label>
                    <label class="flex-1">
                        <input type="radio" name="tipe" value="servis" x-model="tipe" class="peer sr-only">
                        <div class="flex items-center justify-center gap-2 py-2 rounded-lg border cursor-pointer text-sm font-medium text-gray-600 peer-checked:bg-brand peer-checked:text-white peer-checked:border-brand">
                            @include('partials.icon', ['name' => 'wrench']) Servis / Work Order
                        </div>
                    </label>
                </div>
            </div>

            {{-- Info servis --}}
            <div class="card p-4 space-y-3" x-show="tipe === 'servis'" x-cloak>
                @php
                    $chevron = '<svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>';
                @endphp
                <div class="grid md:grid-cols-2 gap-3">
                    {{-- Pelanggan --}}
                    <div class="relative" @click.outside="custOpen=false">
                        <label class="form-label">Pelanggan</label>
                        <input type="hidden" name="customer_id" :value="customer_id">
                        <button type="button" @click="custOpen=!custOpen; if(custOpen)$nextTick(()=>$refs.custQ.focus())"
                                class="form-input flex items-center justify-between gap-2 text-left">
                            <span class="truncate" :class="!customer_id && 'text-gray-400'" x-text="custLabel() || '— pilih pelanggan —'"></span>
                            {!! $chevron !!}
                        </button>
                        <div x-show="custOpen" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                            <div class="p-2 border-b border-gray-100"><input x-ref="custQ" x-model="custQ" placeholder="Cari nama / HP…" class="w-full rounded-lg border-gray-300 text-sm"></div>
                            <div class="max-h-56 overflow-y-auto py-1">
                                <template x-for="c in custFiltered" :key="c.id">
                                    <button type="button" @click="pilihPelanggan(c)" class="w-full text-left px-3 py-2 text-sm hover:bg-brand-light" x-text="c.nama + (c.hp ? ' ('+c.hp+')' : '')"></button>
                                </template>
                                <div x-show="!custFiltered.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
                            </div>
                        </div>
                        <a href="{{ route('customers.create') }}" target="_blank" class="inline-block mt-1 text-xs text-brand hover:underline">+ pelanggan baru</a>
                    </div>

                    {{-- Kendaraan --}}
                    <div class="relative" @click.outside="vehOpen=false">
                        <label class="form-label">Kendaraan</label>
                        <input type="hidden" name="vehicle_id" :value="vehicle_id">
                        <button type="button" :disabled="!customer_id" @click="vehOpen=!vehOpen; if(vehOpen)$nextTick(()=>$refs.vehQ.focus())"
                                class="form-input flex items-center justify-between gap-2 text-left" :class="!customer_id && 'opacity-50 cursor-not-allowed'">
                            <span class="truncate" :class="!vehicle_id && 'text-gray-400'" x-text="vehLabel() || (customer_id ? '— pilih kendaraan —' : 'pilih pelanggan dulu')"></span>
                            {!! $chevron !!}
                        </button>
                        <div x-show="vehOpen" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                            <div class="p-2 border-b border-gray-100"><input x-ref="vehQ" x-model="vehQ" placeholder="Cari plat / tipe…" class="w-full rounded-lg border-gray-300 text-sm"></div>
                            <div class="max-h-56 overflow-y-auto py-1">
                                <template x-for="v in vehFiltered" :key="v.id">
                                    <button type="button" @click="pilihKendaraan(v)" class="w-full text-left px-3 py-2 text-sm hover:bg-brand-light" x-text="`${v.plat} — ${v.merk||''} ${v.tipe||''}`"></button>
                                </template>
                                <div x-show="!vehFiltered.length" class="px-3 py-3 text-sm text-gray-400">Belum ada kendaraan untuk pelanggan ini.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Mekanik --}}
                    <div class="relative" @click.outside="mekOpen=false">
                        <label class="form-label">Mekanik</label>
                        <input type="hidden" name="mekanik_id" :value="mekanik_id">
                        <button type="button" @click="mekOpen=!mekOpen; if(mekOpen)$nextTick(()=>$refs.mekQ.focus())"
                                class="form-input flex items-center justify-between gap-2 text-left">
                            <span class="truncate" :class="!mekanik_id && 'text-gray-400'" x-text="mekLabel() || '— pilih mekanik —'"></span>
                            {!! $chevron !!}
                        </button>
                        <div x-show="mekOpen" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                            <div class="p-2 border-b border-gray-100"><input x-ref="mekQ" x-model="mekQ" placeholder="Cari mekanik…" class="w-full rounded-lg border-gray-300 text-sm"></div>
                            <div class="max-h-56 overflow-y-auto py-1">
                                <template x-for="m in mekFiltered" :key="m.id">
                                    <button type="button" @click="pilihMekanik(m)" class="w-full text-left px-3 py-2 text-sm hover:bg-brand-light" x-text="m.name"></button>
                                </template>
                                <div x-show="!mekFiltered.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status_servis" x-model="status_servis" class="form-input">
                            <option value="antri">Antri</option>
                            <option value="dikerjakan">Dikerjakan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Keluhan</label>
                    <textarea name="keluhan" x-model="keluhan" rows="2" class="form-input" placeholder="Keluhan / gejala kendaraan…"></textarea>
                </div>
            </div>

            {{-- Pencarian item --}}
            <div class="card p-4">
                <div class="relative" @click.outside="cari = ''">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                        @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                    </span>
                    <input type="text" x-model="cari" placeholder="Cari / scan barcode sparepart / jasa…"
                           @keydown.enter.prevent="if (hasilCari.length) tambah(hasilCari[0])"
                           class="w-full rounded-lg border-gray-300 text-sm pl-9">
                    <div x-show="cari.length" x-cloak
                         class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-72 overflow-y-auto">
                        <template x-for="r in hasilCari" :key="r.tipe + r.ref_id">
                            <button type="button" @click="tambah(r)"
                                    class="w-full text-left px-3 py-2 hover:bg-brand-light text-sm flex justify-between items-center">
                                <span>
                                    <span class="px-1.5 py-0.5 rounded text-xs mr-2"
                                          :class="r.tipe === 'part' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
                                          x-text="r.tipe"></span>
                                    <span x-text="r.nama"></span>
                                    <span class="text-gray-400 text-xs" x-show="r.tipe === 'part'" x-text="` (stok ${r.stok})`"></span>
                                </span>
                                <span class="text-gray-500" x-text="rp(r.harga)"></span>
                            </button>
                        </template>
                        <div x-show="!hasilCari.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
                    </div>
                </div>

                {{-- Keranjang --}}
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
                                        <span class="text-xs text-gray-400" x-show="it.tipe === 'part'" x-text="`stok ${it.stok}`"></span>
                                    </td>
                                    <td><input type="number" min="1" x-model.number="it.qty" @input="batasiStok(it)" class="w-full rounded border-gray-300 text-sm text-center"></td>
                                    <td><input type="number" min="0" x-model.number="it.harga" class="w-full rounded border-gray-300 text-sm text-right"></td>
                                    <td><input type="number" min="0" x-model.number="it.diskon" class="w-full rounded border-gray-300 text-sm text-right"></td>
                                    <td class="text-right font-medium" x-text="rp(it.qty * it.harga - it.diskon)"></td>
                                    <td class="text-right"><button type="button" @click="items.splice(i,1)" class="text-red-600">✕</button></td>
                                </tr>
                            </template>
                            <tr x-show="!items.length"><td colspan="6" class="py-8 text-center text-gray-400">Keranjang kosong. Cari item di atas.</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- hidden input untuk submit item --}}
                <template x-for="(it, i) in items" :key="'h'+i">
                    <div>
                        <input type="hidden" :name="`items[${i}][tipe]`" :value="it.tipe">
                        <input type="hidden" :name="`items[${i}][ref_id]`" :value="it.ref_id">
                        <input type="hidden" :name="`items[${i}][nama]`" :value="it.nama">
                        <input type="hidden" :name="`items[${i}][qty]`" :value="it.qty">
                        <input type="hidden" :name="`items[${i}][harga]`" :value="it.harga">
                        <input type="hidden" :name="`items[${i}][diskon]`" :value="it.diskon || 0">
                    </div>
                </template>
            </div>
        </div>

        {{-- KANAN: ringkasan & bayar --}}
        <div class="space-y-4">
            <div class="card p-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform / Channel</label>
                    <select name="platform_id" x-model="platform_id" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($platforms as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Promo</label>
                    <select x-model="promo_dropdown" :disabled="voucherPromo" class="w-full rounded-lg border-gray-300 text-sm disabled:opacity-50">
                        <option value="">— tanpa promo —</option>
                        @foreach ($promos as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-amber-600 mt-1" x-show="promo_dropdown && !voucherPromo && promoPotong === 0" x-cloak>Syarat promo belum terpenuhi.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Voucher</label>
                    <div class="flex gap-1" x-show="!voucherPromo">
                        <input type="text" x-model="voucherKode" @keydown.enter.prevent="pakaiVoucher()" placeholder="Masukkan kode…"
                               class="flex-1 rounded-lg border-gray-300 text-sm uppercase">
                        <button type="button" @click="pakaiVoucher()" class="px-3 py-1.5 bg-gray-800 text-white rounded-lg text-sm">Pakai</button>
                    </div>
                    <div x-show="voucherPromo" x-cloak class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-1.5 text-sm">
                        <span class="text-green-700">🎟️ <span x-text="voucherPromo?.nama"></span></span>
                        <button type="button" @click="hapusVoucher()" class="text-red-600">✕</button>
                    </div>
                    <p class="text-xs text-rose-600 mt-1" x-show="voucherMsg" x-text="voucherMsg" x-cloak></p>
                </div>
                {{-- promo_id efektif yang dikirim ke server --}}
                <input type="hidden" name="promo_id" :value="voucherPromo ? voucherPromo.id : promo_dropdown">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diskon Manual (Rp)</label>
                    <input type="number" min="0" name="diskon" x-model.number="diskon" class="w-full rounded-lg border-gray-300 text-sm text-right">
                </div>
            </div>

            <div class="card p-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span x-text="rp(subtotal)"></span></div>
                <div class="flex justify-between text-rose-600" x-show="diskonTotal > 0"><span>Diskon</span><span x-text="'- ' + rp(diskonTotal)"></span></div>
                @if ($pajakAktif)
                    <div class="flex justify-between"><span class="text-gray-500">Pajak ({{ $pajakPersen }}%)</span><span x-text="rp(pajak)"></span></div>
                @endif
                <div class="flex justify-between text-lg font-bold border-t pt-2"><span>Total</span><span class="text-brand" x-text="rp(total)"></span></div>
            </div>

            <div class="card p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Pembayaran</label>
                    <button type="button" @click="bayar.push({ metode: 'tunai', jumlah: null })" class="text-xs text-brand hover:underline">+ Metode lain (split)</button>
                </div>

                <template x-for="(p, i) in bayar" :key="i">
                    <div class="flex gap-1 items-center">
                        <select x-model="p.metode" :name="`payments[${i}][metode]`" class="rounded-lg border-gray-300 text-xs w-24 capitalize">
                            <template x-for="m in ['tunai','transfer','qris','kartu']" :key="m"><option :value="m" x-text="m"></option></template>
                        </select>
                        <input type="number" min="0" x-model.number="p.jumlah" :name="`payments[${i}][jumlah]`" class="flex-1 rounded-lg border-gray-300 text-sm text-right" placeholder="0">
                        <button type="button" x-show="bayar.length > 1" @click="bayar.splice(i, 1)" class="text-red-600 px-1">✕</button>
                    </div>
                </template>

                <div class="flex gap-1">
                    <button type="button" @click="bayar[0].jumlah = total" class="flex-1 py-1 bg-gray-100 rounded text-xs">Uang Pas</button>
                    <template x-for="n in [50000,100000,200000]" :key="n">
                        <button type="button" @click="bayar[0].jumlah = n" class="flex-1 py-1 bg-gray-100 rounded text-xs" x-text="rp(n)"></button>
                    </template>
                </div>

                <div class="flex justify-between text-sm border-t pt-2" x-show="totalBayar > 0">
                    <span class="text-gray-500">Total Dibayar</span><span x-text="rp(totalBayar)"></span>
                </div>
                <div class="flex justify-between text-sm" x-show="totalBayar > 0 && kembalian >= 0">
                    <span class="text-gray-500">Kembalian</span>
                    <span class="font-semibold text-emerald-600" x-text="rp(kembalian)"></span>
                </div>
                <div class="flex justify-between text-sm" x-show="totalBayar > 0 && kembalian < 0">
                    <span class="text-rose-600">Kurang</span>
                    <span class="font-semibold text-rose-600" x-text="rp(-kembalian)"></span>
                </div>

                <button class="w-full py-3 bg-brand text-white rounded-lg font-medium disabled:opacity-40" :disabled="!items.length">
                    <span x-text="tipe === 'servis' && status_servis !== 'selesai' ? 'Simpan Order' : 'Bayar &amp; Simpan'"></span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const PARTS = @json($parts);
    const SERVICES = @json($services);
    const VEHICLES = @json($vehicles);
    const CUSTOMERS = @json($customers);
    const MEKANIKS = @json($mekaniks);
    const PROMOS = @json($promos);
    const PAJAK = { aktif: {{ $pajakAktif ? 'true' : 'false' }}, persen: {{ $pajakPersen }} };

    function pos() {
        return {
            tipe: 'penjualan',
            customer_id: '', vehicle_id: '', mekanik_id: '', keluhan: '', status_servis: 'antri',
            platform_id: '{{ $platforms->firstWhere('nama', 'Kasir')->id ?? $platforms->first()->id ?? '' }}',
            promo_dropdown: '', diskon: 0, bayar: [{ metode: 'tunai', jumlah: null }],
            voucherKode: '', voucherPromo: null, voucherMsg: '',
            items: [], cari: '',
            custOpen: false, custQ: '', vehOpen: false, vehQ: '', mekOpen: false, mekQ: '',

            // ── Voucher ──
            async pakaiVoucher() {
                this.voucherMsg = '';
                const kode = this.voucherKode.trim();
                if (!kode) return;
                try {
                    const url = `{{ route('pos.voucher') }}?kode=${encodeURIComponent(kode)}&subtotal=${this.subtotal - (this.diskon || 0)}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.ok) { this.voucherPromo = data.promo; this.promo_dropdown = ''; this.voucherKode = ''; }
                    else this.voucherMsg = data.msg;
                } catch (e) { this.voucherMsg = 'Gagal memeriksa voucher.'; }
            },
            hapusVoucher() { this.voucherPromo = null; this.voucherMsg = ''; },

            // ── Pelanggan (searchable) ──
            get custFiltered() {
                const q = this.custQ.toLowerCase().trim();
                return CUSTOMERS.filter(c => !q || (c.nama + ' ' + (c.hp || '')).toLowerCase().includes(q)).slice(0, 50);
            },
            custLabel() {
                const c = CUSTOMERS.find(x => x.id == this.customer_id);
                return c ? c.nama + (c.hp ? ` (${c.hp})` : '') : '';
            },
            pilihPelanggan(c) { this.customer_id = c.id; this.vehicle_id = ''; this.custOpen = false; this.custQ = ''; },

            // ── Kendaraan (searchable, terikat pelanggan) ──
            get kendaraanTerfilter() {
                return VEHICLES.filter(v => !this.customer_id || v.customer_id == this.customer_id);
            },
            get vehFiltered() {
                const q = this.vehQ.toLowerCase().trim();
                return this.kendaraanTerfilter.filter(v => !q || (`${v.plat} ${v.merk||''} ${v.tipe||''}`).toLowerCase().includes(q));
            },
            vehLabel() {
                const v = VEHICLES.find(x => x.id == this.vehicle_id);
                return v ? `${v.plat} — ${v.merk||''} ${v.tipe||''}`.trim() : '';
            },
            pilihKendaraan(v) { this.vehicle_id = v.id; this.vehOpen = false; this.vehQ = ''; },

            // ── Mekanik (searchable) ──
            get mekFiltered() {
                const q = this.mekQ.toLowerCase().trim();
                return MEKANIKS.filter(m => !q || m.name.toLowerCase().includes(q));
            },
            mekLabel() {
                const m = MEKANIKS.find(x => x.id == this.mekanik_id);
                return m ? m.name : '';
            },
            pilihMekanik(m) { this.mekanik_id = m.id; this.mekOpen = false; this.mekQ = ''; },
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
                if (ada) { ada.qty++; this.batasiStok(ada); }
                else this.items.push({ ...r, qty: 1, diskon: 0 });
                this.cari = '';
            },
            batasiStok(it) {
                if (it.tipe === 'part' && it.qty > it.stok) it.qty = it.stok;
                if (it.qty < 1) it.qty = 1;
            },
            get subtotal() { return this.items.reduce((s, it) => s + (it.qty * it.harga - (it.diskon || 0)), 0); },
            get promoAktif() {
                return this.voucherPromo || PROMOS.find(x => x.id == this.promo_dropdown) || null;
            },
            get promoPotong() {
                const p = this.promoAktif;
                if (!p) return 0;
                const dasar = this.subtotal - (this.diskon || 0);
                if (p.min_belanja && dasar < p.min_belanja) return 0;
                let pot = 0;
                if (p.jenis === 'persen') pot = dasar * p.nilai / 100;
                else if (p.jenis === 'nominal') pot = Number(p.nilai);
                else if (p.jenis === 'harga_khusus') pot = Math.max(0, dasar - p.nilai);
                return Math.min(pot, dasar);
            },
            get diskonTotal() { return Math.min(this.subtotal, (this.diskon || 0) + this.promoPotong); },
            get pajak() { return PAJAK.aktif ? Math.round((this.subtotal - this.diskonTotal) * PAJAK.persen / 100) : 0; },
            get total() { return this.subtotal - this.diskonTotal + this.pajak; },
            get totalBayar() { return this.bayar.reduce((s, p) => s + (p.jumlah || 0), 0); },
            get kembalian() { return this.totalBayar - this.total; },

            siap() {
                if (!this.items.length) return false;
                if (this.tipe === 'servis' && (!this.customer_id || !this.vehicle_id)) {
                    Swal.fire({ icon: 'warning', title: 'Lengkapi pelanggan & kendaraan untuk servis.' });
                    return false;
                }
                return true;
            },
            rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
