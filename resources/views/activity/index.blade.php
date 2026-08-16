@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('header', 'Log Aktivitas')

@section('content')
    @php
        $badge = [
            'login' => 'badge-gray', 'transaksi_baru' => 'badge-green', 'batal_transaksi' => 'badge-red',
            'edit_transaksi' => 'badge-amber', 'retur' => 'badge-red',
            'ajukan_edit' => 'badge-blue', 'ajukan_batal' => 'badge-blue',
            'setujui_edit' => 'badge-green', 'setujui_batal' => 'badge-green',
            'tolak_edit' => 'badge-red', 'tolak_batal' => 'badge-red',
            'pembelian' => 'badge-green', 'edit_pembelian' => 'badge-amber', 'batal_pembelian' => 'badge-red', 'pembelian_lunas' => 'badge-blue', 'retur_pembelian' => 'badge-red',
            'stok_opname' => 'badge-amber', 'bayar_gaji' => 'badge-blue', 'user_baru' => 'badge-blue',
            'buka_shift' => 'badge-green', 'tutup_shift' => 'badge-gray',
        ];
    @endphp
    <div class="card p-5">
        <form method="GET" class="flex gap-2 mb-4">
            <select name="aksi" class="form-input max-w-xs" onchange="this.form.submit()">
                <option value="">— semua aksi —</option>
                @foreach ($aksiList as $a)
                    <option value="{{ $a }}" @selected(request('aksi') === $a)>{{ str_replace('_', ' ', $a) }}</option>
                @endforeach
            </select>
        </form>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Detail</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap text-gray-600">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? '-' }}</td>
                        <td><span class="badge {{ $badge[$log->aksi] ?? 'badge-gray' }}">{{ str_replace('_', ' ', $log->aksi) }}</span></td>
                        <td class="text-gray-600">{{ $log->deskripsi }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-gray-400">Belum ada aktivitas.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
@endsection
