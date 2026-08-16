@extends('layouts.app')
@section('title', 'Retur ' . $retur->no)
@section('header', 'Retur ' . $retur->no)

@section('content')
    <div class="card max-w-2xl">
        <div class="p-5 grid md:grid-cols-4 gap-3 text-sm border-b">
            <div><div class="text-gray-500">No. Retur</div><div class="font-mono font-medium">{{ $retur->no }}</div></div>
            <div><div class="text-gray-500">Tanggal</div><div>{{ $retur->tgl?->format('d/m/Y H:i') }}</div></div>
            <div><div class="text-gray-500">Transaksi</div><div class="font-mono"><a href="{{ route('transactions.show', $retur->transaction_id) }}" class="text-brand hover:underline">{{ $retur->transaction?->no_nota }}</a></div></div>
            <div><div class="text-gray-500">Oleh</div><div>{{ $retur->user?->name }}</div></div>
        </div>
        <div class="p-5">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($retur->items as $it)
                        <tr>
                            <td class="py-2">{{ $it->nama }} @if($it->part_id)<span class="badge badge-green ml-1">restock</span>@endif</td>
                            <td class="text-center">{{ $it->qty }}</td>
                            <td class="text-right">{{ rupiah($it->harga) }}</td>
                            <td class="text-right font-medium">{{ rupiah($it->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t"><td colspan="3" class="py-3 text-right font-semibold">Total Retur</td>
                        <td class="py-3 text-right text-lg font-bold text-rose-600">{{ rupiah($retur->total) }}</td></tr>
                </tfoot>
            </table>
            <div class="mt-3 text-sm"><span class="text-gray-500">Alasan:</span> {{ $retur->alasan }}</div>
        </div>
    </div>
    <a href="{{ route('returns.index') }}" class="inline-block mt-4 btn-secondary">← Daftar Retur</a>
@endsection
