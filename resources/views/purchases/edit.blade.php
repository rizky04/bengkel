@extends('layouts.app')
@section('title', 'Edit Pembelian')
@section('header', 'Edit Pembelian — ' . $purchase->no)

@section('content')
    <form method="POST" action="{{ route('purchases.update', $purchase) }}" x-data="editBeli()" class="space-y-4">
        @csrf @method('PUT')

        <div class="grid lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-4">
                <div class="card p-4">
                    <label class="form-label">Tambah Barang</label>
                    <div class="relative" @click.outside="cari = ''">
                        <input type="text" x-model="cari" placeholder="Cari sparepart…"
                               @keydown.enter.prevent="if (hasil.length) tambah(hasil[0])"
                               class="w-full rounded-lg border-gray-300 text-sm">
                        <div x-show="cari.length" x-cloak class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="p in hasil" :key="p.id">
                                <button type="button" @click="tambah(p)" class="w-full text-left px-3 py-2 hover:bg-brand-light text-sm flex justify-between">
                                    <span x-text="p.nama"></span><span class="text-gray-500" x-text="rp(p.harga_beli)"></span>
                                </button>
                            </template>
                            <div x-show="!hasil.length" class="px-3 py-3 text-sm text-gray-400">Tidak ditemukan.</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto mt-4">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                                <tr><th class="py-2">Barang</th><th class="text-center w-24">Qty</th><th class="text-right w-36">Harga Beli</th><th class="text-right w-32">Subtotal</th><th></th></tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="(it, i) in items" :key="i">
                                    <tr>
                                        <td class="py-2" x-text="it.nama"></td>
                                        <td><input type="number" min="1" x-model.number="it.qty" class="w-full rounded border-gray-300 text-sm text-center"></td>
                                        <td><input type="number" min="0" x-model.number="it.harga_beli" class="w-full rounded border-gray-300 text-sm text-right"></td>
                                        <td class="text-right font-medium" x-text="rp(it.qty * it.harga_beli)"></td>
                                        <td class="text-right"><button type="button" @click="items.splice(i,1)" class="text-red-600">✕</button></td>
                                    </tr>
                                </template>
                                <tr x-show="!items.length"><td colspan="5" class="py-8 text-center text-gray-400">Tidak ada item.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <template x-for="(it, i) in items" :key="'h'+i">
                        <div>
                            <input type="hidden" :name="`items[${i}][id]`" :value="it.id || ''">
                            <input type="hidden" :name="`items[${i}][part_id]`" :value="it.part_id">
                            <input type="hidden" :name="`items[${i}][qty]`" :value="it.qty">
                            <input type="hidden" :name="`items[${i}][harga_beli]`" :value="it.harga_beli">
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-4">
                <div class="card p-4 space-y-3">
                    @include('partials.search-select', ['name' => 'supplier_id', 'label' => 'Supplier', 'value' => $purchase->supplier_id, 'placeholder' => '— tanpa supplier —', 'options' => $suppliers->pluck('nama', 'id')])
                    @include('partials.field', ['name' => 'tgl', 'label' => 'Tanggal', 'type' => 'date', 'value' => $purchase->tgl?->format('Y-m-d'), 'required' => true])
                    @include('partials.field', ['name' => 'status', 'label' => 'Status Bayar', 'type' => 'select', 'value' => $purchase->status, 'options' => ['lunas' => 'Lunas', 'belum_lunas' => 'Belum Lunas']])
                </div>
                <div class="card p-4 text-sm">
                    <div class="flex justify-between text-lg font-bold"><span>Total</span><span class="text-brand" x-text="rp(total)"></span></div>
                    <p class="text-xs text-gray-400 pt-1">Menaikkan qty menambah stok; menurunkan mengembalikan stok.</p>
                </div>
                <div class="flex gap-2">
                    <button class="btn-primary flex-1" :disabled="!items.length">Simpan Perubahan</button>
                    <a href="{{ route('purchases.show', $purchase) }}" class="btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@php
    $itemsAwal = $purchase->items->map(fn ($it) => [
        'id' => $it->id, 'part_id' => $it->part_id, 'nama' => $it->part?->nama,
        'qty' => (int) $it->qty, 'harga_beli' => (float) $it->harga_beli,
    ])->values();
@endphp
@push('scripts')
<script>
    const PARTS = @json($parts);
    const ITEMS_AWAL = @json($itemsAwal);

    function editBeli() {
        return {
            items: JSON.parse(JSON.stringify(ITEMS_AWAL)),
            cari: '',
            get hasil() {
                const q = this.cari.toLowerCase().trim();
                if (!q) return [];
                return PARTS.filter(p => (p.nama + ' ' + p.kode).toLowerCase().includes(q)).slice(0, 10);
            },
            tambah(p) {
                const ada = this.items.find(it => it.part_id === p.id);
                if (ada) ada.qty++;
                else this.items.push({ id: null, part_id: p.id, nama: p.nama, qty: 1, harga_beli: Number(p.harga_beli) });
                this.cari = '';
            },
            get total() { return this.items.reduce((s, it) => s + (it.qty * it.harga_beli), 0); },
            rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
