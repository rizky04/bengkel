@extends('layouts.app')
@section('title', $part->exists ? 'Edit Sparepart' : 'Tambah Sparepart')
@section('header', ($part->exists ? 'Edit' : 'Tambah') . ' Sparepart')

@section('content')
    <div class="card p-5 max-w-2xl">
        <form method="POST" action="{{ $part->exists ? route('parts.update', $part) : route('parts.store') }}" class="space-y-4">
            @csrf
            @if ($part->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'kode', 'label' => 'Kode / SKU', 'value' => $part->kode, 'required' => true])
                @include('partials.field', ['name' => 'nama', 'label' => 'Nama Barang', 'value' => $part->nama, 'required' => true])
                @include('partials.search-select', [
                    'name' => 'category_id', 'label' => 'Kategori',
                    'value' => $part->category_id, 'placeholder' => '— kategori —',
                    'options' => $categories->pluck('nama', 'id'),
                ])
                @include('partials.field', ['name' => 'satuan', 'label' => 'Satuan', 'value' => $part->satuan ?? 'pcs', 'required' => true])
                @include('partials.field', ['name' => 'harga_beli', 'label' => 'Harga Beli', 'type' => 'number', 'value' => $part->harga_beli ?? 0, 'required' => true])
                @include('partials.field', ['name' => 'harga_jual', 'label' => 'Harga Jual', 'type' => 'number', 'value' => $part->harga_jual ?? 0, 'required' => true])

                @if (! $part->exists)
                    @include('partials.field', ['name' => 'stok', 'label' => 'Stok Awal', 'type' => 'number', 'value' => $part->stok ?? 0, 'required' => true])
                @else
                    <input type="hidden" name="stok" value="{{ $part->stok }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stok Saat Ini</label>
                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm">
                            {{ $part->stok }} {{ $part->satuan }}
                            <span class="text-gray-400">(ubah lewat Pembelian / Opname)</span>
                        </div>
                    </div>
                @endif

                @include('partials.field', ['name' => 'stok_min', 'label' => 'Stok Minimum', 'type' => 'number', 'value' => $part->stok_min ?? 0, 'required' => true])
                @include('partials.field', ['name' => 'lokasi_rak', 'label' => 'Lokasi Rak', 'value' => $part->lokasi_rak])
                @include('partials.search-select', [
                    'name' => 'supplier_id', 'label' => 'Supplier',
                    'value' => $part->supplier_id, 'placeholder' => '— supplier —',
                    'options' => $suppliers->pluck('nama', 'id'),
                ])
            </div>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('parts.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
