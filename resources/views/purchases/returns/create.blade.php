@extends('layouts.app')
@section('title', 'Retur Pembelian')
@section('header', 'Retur Pembelian — ' . $purchase->no)

@section('content')
    <form method="POST" action="{{ route('purchase-returns.store', $purchase) }}" class="max-w-3xl space-y-4"
          x-data="retur()" data-confirm="Proses retur? Stok barang akan dikurangi.">
        @csrf

        <div class="card p-5 text-sm grid md:grid-cols-3 gap-3">
            <div><div class="text-gray-500">No. Pembelian</div><div class="font-mono font-medium">{{ $purchase->no }}</div></div>
            <div><div class="text-gray-500">Supplier</div><div>{{ $purchase->supplier?->nama ?? '-' }}</div></div>
            <div><div class="text-gray-500">Tanggal</div><div>{{ $purchase->tgl?->format('d/m/Y') }}</div></div>
        </div>

        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Pilih Item yang Diretur</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-2 px-4">Barang</th>
                        <th class="text-center px-2">Dibeli</th>
                        <th class="text-center px-2">Sudah Retur</th>
                        <th class="text-center px-2">Sisa</th>
                        <th class="text-right px-2">Harga Beli</th>
                        <th class="text-center px-2 w-28">Qty Retur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($items as $i => $it)
                        <tr>
                            <td class="py-2 px-4">
                                {{ $it->part?->nama ?? '—' }}
                                <span class="text-xs text-gray-400 ml-1">{{ $it->part?->kode }}</span>
                                <input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $it->id }}">
                            </td>
                            <td class="text-center px-2">{{ $it->qty }}</td>
                            <td class="text-center px-2 text-gray-400">{{ $it->qtyDiretur() }}</td>
                            <td class="text-center px-2 font-medium">{{ $it->sisaRetur() }}</td>
                            <td class="text-right px-2">{{ rupiah($it->harga_beli) }}</td>
                            <td class="text-center px-2">
                                <input type="number" min="0" max="{{ $it->sisaRetur() }}"
                                       name="items[{{ $i }}][qty]" value="0"
                                       x-model.number="qty[{{ $i }}]"
                                       @input="cap({{ $i }}, {{ $it->sisaRetur() }}, {{ $it->harga_beli }})"
                                       class="w-20 rounded-lg border-gray-300 text-sm text-center">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td colspan="5" class="py-3 px-4 text-right font-semibold">Total Nilai Retur</td>
                        <td class="py-3 px-2 text-center font-bold text-rose-600" x-text="rp(total)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card p-5">
            @include('partials.field', ['name' => 'alasan', 'label' => 'Alasan Retur', 'required' => true, 'placeholder' => 'mis. barang cacat / tidak sesuai pesanan'])
        </div>

        <div class="flex gap-2">
            <button class="btn-primary" :disabled="total <= 0" :class="total <= 0 && 'opacity-40'">Proses Retur</button>
            <a href="{{ route('purchases.show', $purchase) }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function retur() {
        return {
            qty: {}, hargaMap: {}, total: 0,
            cap(i, sisa, harga) {
                if (this.qty[i] > sisa) this.qty[i] = sisa;
                if (this.qty[i] < 0 || !this.qty[i]) this.qty[i] = 0;
                this.hargaMap[i] = harga;
                this.total = Object.keys(this.qty).reduce((s, k) => s + (this.qty[k] || 0) * (this.hargaMap[k] || 0), 0);
            },
            rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
