@extends('layouts.app')
@section('title', 'Pembelian Baru')
@section('header', 'Pembelian Baru')

@section('content')
<form method="POST" action="{{ route('purchases.store') }}" x-data="pembelian()">
    @csrf

    <div class="card p-5 mb-4">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. Pembelian</label>
                <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm font-mono">{{ $noBaru }}</div>
            </div>
            @include('partials.search-select', [
                'name' => 'supplier_id', 'label' => 'Supplier',
                'placeholder' => '— tanpa supplier —', 'options' => $suppliers->pluck('nama', 'id'),
            ])
            @include('partials.field', ['name' => 'tgl', 'label' => 'Tanggal', 'type' => 'date', 'value' => date('Y-m-d'), 'required' => true])
        </div>
    </div>

    <div class="card">
        <div class="px-5 py-3 border-b font-semibold text-gray-700 flex justify-between items-center">
            <span>Item Barang</span>
            <button type="button" @click="tambah()" class="px-3 py-1.5 bg-brand text-white rounded-lg text-sm">+ Baris</button>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-2 w-2/5">Barang</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center w-24">Qty</th>
                            <th class="text-right w-36">Harga Beli</th>
                            <th class="text-right w-32">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="(it, i) in items" :key="i">
                            <tr>
                                <td class="py-2">
                                    <select :name="`items[${i}][part_id]`" x-model.number="it.part_id" @change="isiHarga(it)"
                                            class="w-full rounded-lg border-gray-300 text-sm" required>
                                        <option value="">— pilih barang —</option>
                                        @foreach ($parts as $part)
                                            <option value="{{ $part->id }}">{{ $part->kode }} — {{ $part->nama }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-center text-gray-500" x-text="stokPart(it.part_id)"></td>
                                <td>
                                    <input type="number" min="1" :name="`items[${i}][qty]`" x-model.number="it.qty"
                                           class="w-full rounded-lg border-gray-300 text-sm text-center" required>
                                </td>
                                <td>
                                    <input type="number" min="0" :name="`items[${i}][harga_beli]`" x-model.number="it.harga_beli"
                                           class="w-full rounded-lg border-gray-300 text-sm text-right" required>
                                </td>
                                <td class="text-right font-medium" x-text="rp(it.qty * it.harga_beli)"></td>
                                <td class="text-right">
                                    <button type="button" @click="items.splice(i, 1)" class="text-red-600 hover:underline">Hapus</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!items.length">
                            <td colspan="6" class="py-6 text-center text-gray-400">Belum ada item. Klik "+ Baris".</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td colspan="4" class="py-3 text-right font-semibold">Total</td>
                            <td class="py-3 text-right text-lg font-bold text-brand" x-text="rp(total)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex gap-2 pt-4">
                <button class="btn-primary disabled:opacity-40"
                        :disabled="!items.length">Simpan &amp; Tambah Stok</button>
                <a href="{{ route('purchases.index') }}" class="btn-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const PARTS = @json($parts->keyBy('id'));

    function pembelian() {
        return {
            items: [{ part_id: '', qty: 1, harga_beli: 0 }],
            tambah() { this.items.push({ part_id: '', qty: 1, harga_beli: 0 }); },
            isiHarga(it) {
                const p = PARTS[it.part_id];
                if (p) it.harga_beli = Number(p.harga_beli);
            },
            stokPart(id) { return PARTS[id] ? PARTS[id].stok + ' ' + PARTS[id].satuan : '-'; },
            get total() { return this.items.reduce((s, it) => s + (it.qty || 0) * (it.harga_beli || 0), 0); },
            rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
