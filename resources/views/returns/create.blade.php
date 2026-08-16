@extends('layouts.app')
@section('title', 'Buat Retur')
@section('header', 'Retur — ' . $transaction->no_nota)

@section('content')
    <form method="POST" action="{{ route('returns.store') }}" class="max-w-3xl space-y-4"
          x-data="retur()" data-confirm="Proses retur? Stok part akan dikembalikan.">
        @csrf
        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">

        <div class="card p-5 text-sm grid md:grid-cols-3 gap-3">
            <div><div class="text-gray-500">Transaksi</div><div class="font-mono font-medium">{{ $transaction->no_nota }}</div></div>
            <div><div class="text-gray-500">Pelanggan</div><div>{{ $transaction->customer?->nama ?? '-' }}</div></div>
            <div><div class="text-gray-500">Kendaraan</div><div>{{ $transaction->vehicle?->plat ?? '-' }}</div></div>
        </div>

        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Pilih Item yang Diretur</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Item</th><th class="text-center">Terjual</th><th class="text-center">Sudah Retur</th><th class="text-center">Sisa</th><th class="text-right">Harga</th><th class="text-center w-28">Qty Retur</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($items as $i => $it)
                        <tr>
                            <td class="py-2">
                                <span class="px-1.5 py-0.5 rounded text-xs mr-1 {{ $it->tipe === 'part' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $it->tipe }}</span>
                                {{ $it->nama }}
                                <input type="hidden" name="items[{{ $i }}][tx_item_id]" value="{{ $it->id }}">
                            </td>
                            <td class="text-center">{{ $it->qty }}</td>
                            <td class="text-center text-gray-400">{{ $it->qtyDiretur() }}</td>
                            <td class="text-center font-medium">{{ $it->sisaRetur() }}</td>
                            <td class="text-right">{{ rupiah($it->harga) }}</td>
                            <td class="text-center">
                                <input type="number" min="0" max="{{ $it->sisaRetur() }}" name="items[{{ $i }}][qty]" value="0"
                                       x-model.number="qty[{{ $i }}]" @input="cap({{ $i }}, {{ $it->sisaRetur() }}, {{ $it->harga }})"
                                       class="w-20 rounded-lg border-gray-300 text-sm text-center">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t"><td colspan="5" class="py-3 text-right font-semibold">Total Retur</td>
                        <td class="py-3 text-center font-bold text-rose-600" x-text="rp(total)"></td></tr>
                </tfoot>
            </table>
        </div>

        <div class="card p-5">
            @include('partials.field', ['name' => 'alasan', 'label' => 'Alasan Retur', 'required' => true, 'placeholder' => 'mis. barang rusak / salah beli'])
        </div>

        <div class="flex gap-2">
            <button class="btn-primary" :disabled="total <= 0" :class="total <= 0 && 'opacity-40'">Proses Retur</button>
            <a href="{{ route('transactions.show', $transaction) }}" class="btn-secondary">Batal</a>
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
