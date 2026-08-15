@extends('layouts.app')
@section('title', 'Stok Opname')
@section('header', 'Stok Opname')

@section('content')
    <div class="card p-5">
        <p class="text-sm text-gray-500 mb-4">
            Isi <strong>jumlah fisik</strong> hasil hitung di gudang. Baris yang dikosongkan tidak diubah.
            Selisih akan dicatat otomatis di kartu stok.
        </p>

        <form method="GET" class="flex gap-2 mb-4">
            <input name="q" value="{{ $q }}" placeholder="Cari nama / kode…" class="rounded-lg border-gray-300 text-sm">
            <button class="btn-secondary btn-sm">Cari</button>
        </form>

        <form method="POST" action="{{ route('stock.opname.store') }}" x-data="{ }"
              data-confirm="Simpan hasil opname? Stok akan disesuaikan.">
            @csrf

            <div class="mb-4">
                @include('partials.field', ['name' => 'keterangan', 'label' => 'Keterangan (opsional)', 'placeholder' => 'mis. Opname bulanan Agustus'])
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr><th class="py-2">Kode</th><th>Nama</th><th class="text-center">Stok Sistem</th><th class="text-center w-32">Jumlah Fisik</th><th class="text-center w-28">Selisih</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($parts as $p)
                            <tr class="hover:bg-gray-50" x-data="{ fisik: '' }">
                                <td class="py-2 font-mono text-xs">{{ $p->kode }}</td>
                                <td class="font-medium">{{ $p->nama }}</td>
                                <td class="text-center">{{ $p->stok }} {{ $p->satuan }}</td>
                                <td>
                                    <input type="number" min="0" name="fisik[{{ $p->id }}]" x-model="fisik"
                                           placeholder="—" class="w-full rounded-lg border-gray-300 text-sm text-center">
                                </td>
                                <td class="text-center font-medium"
                                    x-text="fisik === '' ? '' : (fisik - {{ $p->stok }} > 0 ? '+' : '') + (fisik - {{ $p->stok }})"
                                    :class="fisik === '' ? '' : (fisik - {{ $p->stok }}) < 0 ? 'text-rose-600' : (fisik - {{ $p->stok }}) > 0 ? 'text-emerald-600' : 'text-gray-400'"></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-400">Tidak ada barang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($parts->count() >= 100)
                <p class="text-xs text-amber-600 mt-3">Menampilkan 100 barang pertama — gunakan pencarian untuk opname bertahap.</p>
            @endif

            <div class="flex gap-2 pt-4">
                <button class="btn-primary">Simpan Opname</button>
                <a href="{{ route('stock.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
