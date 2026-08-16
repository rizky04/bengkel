@extends('layouts.app')
@section('title', 'Retur Pembelian ' . $retur->no)
@section('header', 'Retur Pembelian ' . $retur->no)

@section('content')
    <div class="max-w-3xl space-y-4">
        <div class="card p-5 text-sm grid md:grid-cols-4 gap-3">
            <div><div class="text-gray-500">No. Retur</div><div class="font-mono font-medium">{{ $retur->no }}</div></div>
            <div><div class="text-gray-500">No. Pembelian</div>
                <a href="{{ route('purchases.show', $retur->purchase) }}" class="font-mono text-brand hover:underline">{{ $retur->purchase?->no }}</a>
            </div>
            <div><div class="text-gray-500">Supplier</div><div>{{ $retur->purchase?->supplier?->nama ?? '-' }}</div></div>
            <div><div class="text-gray-500">Tanggal</div><div>{{ $retur->tgl?->format('d/m/Y H:i') }}</div></div>
        </div>

        <div class="card">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Item Retur</div>
            <div class="p-5">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-2">Barang</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Harga Beli</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($retur->items as $it)
                            <tr>
                                <td class="py-2">{{ $it->nama }}</td>
                                <td class="text-center">{{ $it->qty }}</td>
                                <td class="text-right">{{ rupiah($it->harga_beli) }}</td>
                                <td class="text-right font-medium">{{ rupiah($it->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td colspan="3" class="py-3 text-right font-semibold">Total</td>
                            <td class="py-3 text-right text-lg font-bold text-rose-600">{{ rupiah($retur->total) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="mt-4 border-t pt-4 text-sm text-gray-600">
                    <span class="text-gray-500">Alasan:</span> {{ $retur->alasan }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    Dibuat oleh {{ $retur->user?->name }} • {{ $retur->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        <a href="{{ route('purchases.show', $retur->purchase) }}" class="btn-secondary inline-block">← Kembali ke Pembelian</a>
    </div>
@endsection
