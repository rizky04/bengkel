@extends('layouts.app')
@section('title', 'Detail Pembelian')
@section('header', 'Pembelian ' . $purchase->no)

@section('content')
    <div class="card p-5 mb-4">
        <div class="grid md:grid-cols-4 gap-4 text-sm">
            <div><div class="text-gray-500">No. Pembelian</div><div class="font-mono font-medium">{{ $purchase->no }}</div></div>
            <div><div class="text-gray-500">Tanggal</div><div class="font-medium">{{ $purchase->tgl?->format('d/m/Y') }}</div></div>
            <div><div class="text-gray-500">Supplier</div><div class="font-medium">{{ $purchase->supplier?->nama ?? '-' }}</div></div>
            <div><div class="text-gray-500">Total</div><div class="font-bold text-brand">{{ rupiah($purchase->total) }}</div></div>
        </div>
    </div>

    <div class="card">
        <div class="px-5 py-3 border-b font-semibold text-gray-700">Item</div>
        <div class="p-5">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Kode</th><th>Barang</th><th class="text-center">Qty</th><th class="text-right">Harga Beli</th><th class="text-right">Subtotal</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($purchase->items as $it)
                        <tr>
                            <td class="py-2 font-mono text-xs">{{ $it->part?->kode }}</td>
                            <td>{{ $it->part?->nama }}</td>
                            <td class="text-center">{{ $it->qty }}</td>
                            <td class="text-right">{{ rupiah($it->harga_beli) }}</td>
                            <td class="text-right font-medium">{{ rupiah($it->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t"><td colspan="4" class="py-3 text-right font-semibold">Total</td>
                        <td class="py-3 text-right text-lg font-bold text-brand">{{ rupiah($purchase->total) }}</td></tr>
                </tfoot>
            </table>

            <div class="flex gap-2 pt-4 border-t mt-4">
                <a href="{{ route('purchases.index') }}" class="btn-secondary">Kembali</a>
                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}"
                      data-confirm="Batalkan pembelian {{ $purchase->no }}? Stok akan dikurangi kembali.">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Batalkan Pembelian</button>
                </form>
            </div>
        </div>
    </div>
@endsection
