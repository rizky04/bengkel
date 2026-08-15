@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Pelanggan' : 'Tambah Pelanggan')
@section('header', ($customer->exists ? 'Edit' : 'Tambah') . ' Pelanggan')

@section('content')
    <div class="card p-5 max-w-2xl">
        <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="space-y-4">
            @csrf
            @if ($customer->exists) @method('PUT') @endif

            @include('partials.field', ['name' => 'nama', 'label' => 'Nama', 'value' => $customer->nama, 'required' => true])
            @include('partials.field', ['name' => 'hp', 'label' => 'No. HP', 'value' => $customer->hp])
            @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $customer->alamat])
            @include('partials.field', ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea', 'value' => $customer->catatan])

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('customers.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
