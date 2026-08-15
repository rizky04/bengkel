@extends('layouts.app')
@section('title', 'Sparepart')
@section('header', 'Sparepart')

@section('content')
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <form method="GET" class="flex gap-2 items-center">
                <input name="q" value="{{ $q }}" placeholder="Cari nama / kode…" class="rounded-lg border-gray-300 text-sm">
                <label class="text-sm flex items-center gap-1">
                    <input type="checkbox" name="low" value="1" @checked(request('low')) onchange="this.form.submit()"> stok menipis
                </label>
                <button class="btn-secondary btn-sm">Cari</button>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('parts.export') }}" class="btn-secondary btn-sm">⬇ Export</a>
                <button type="button" onclick="document.getElementById('modal-import').classList.remove('hidden')" class="btn-secondary btn-sm">⬆ Import</button>
                <a href="{{ route('parts.create') }}" class="btn-primary">+ Sparepart</a>
            </div>
        </div>

        {{-- Modal import CSV --}}
        <div id="modal-import" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="font-semibold text-gray-800 mb-1">Import Sparepart (CSV/Excel)</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Gunakan file hasil <strong>Export</strong> sebagai template. Kolom: kode, nama, kategori, satuan, harga_beli, harga_jual, stok, stok_min, lokasi_rak.
                    Kode yang sudah ada akan diperbarui (stok tidak diubah — pakai Opname); kode baru dibuat beserta stok awal.
                </p>
                <form method="POST" action="{{ route('parts.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" class="form-input" required>
                    <div class="flex gap-2">
                        <button class="btn-primary">Import</button>
                        <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')" class="btn-secondary">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr><th class="py-2">Kode</th><th>Nama</th><th>Kategori</th><th class="text-right">H. Beli</th><th class="text-right">H. Jual</th><th class="text-center">Stok</th><th></th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($parts as $p)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 font-mono text-xs">{{ $p->kode }}</td>
                            <td class="font-medium">{{ $p->nama }}</td>
                            <td class="text-gray-500">{{ $p->category?->nama }}</td>
                            <td class="text-right">{{ rupiah($p->harga_beli) }}</td>
                            <td class="text-right">{{ rupiah($p->harga_jual) }}</td>
                            <td class="text-center">
                                <span class="{{ $p->stok <= $p->stok_min ? 'text-red-600 font-semibold' : '' }}">{{ $p->stok }}</span>
                                <span class="text-gray-400 text-xs">/{{ $p->stok_min }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('parts.label', $p) }}" target="_blank" class="text-gray-600 hover:underline">Label</a>
                                <a href="{{ route('parts.edit', $p) }}" class="text-brand hover:underline ml-2">Edit</a>
                                <form action="{{ route('parts.destroy', $p) }}" method="POST" class="inline" data-confirm="Hapus {{ $p->nama }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline ml-2">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $parts->links() }}</div>
    </div>
@endsection
