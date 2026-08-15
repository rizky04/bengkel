@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit Supplier' : 'Tambah Supplier')
@section('header', ($supplier->exists ? 'Edit' : 'Tambah') . ' Supplier')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="space-y-4">
            @csrf
            @if ($supplier->exists) @method('PUT') @endif

            @include('partials.field', ['name' => 'nama', 'label' => 'Nama Supplier', 'value' => $supplier->nama, 'required' => true])
            @include('partials.field', ['name' => 'hp', 'label' => 'No. HP', 'value' => $supplier->hp])
            @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $supplier->alamat])
            @include('partials.field', ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'value' => $supplier->catatan])

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('suppliers.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
