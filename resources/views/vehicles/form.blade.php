@extends('layouts.app')
@section('title', $vehicle->exists ? 'Edit Kendaraan' : 'Tambah Kendaraan')
@section('header', ($vehicle->exists ? 'Edit' : 'Tambah') . ' Kendaraan')

@section('content')
    <div class="card p-5 max-w-2xl">
        <form method="POST" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}" class="space-y-4">
            @csrf
            @if ($vehicle->exists) @method('PUT') @endif

            @include('partials.search-select', [
                'name' => 'customer_id', 'label' => 'Pemilik',
                'value' => $vehicle->customer_id, 'required' => true,
                'placeholder' => '— pilih pelanggan —', 'options' => $customers->pluck('nama', 'id'),
            ])

            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'plat', 'label' => 'Plat Nomor', 'value' => $vehicle->plat, 'required' => true])
                @include('partials.field', [
                    'name' => 'jenis', 'label' => 'Jenis', 'type' => 'select',
                    'value' => $vehicle->jenis ?? 'motor', 'options' => ['motor' => 'Motor', 'mobil' => 'Mobil'],
                ])
                @include('partials.field', ['name' => 'merk', 'label' => 'Merk', 'value' => $vehicle->merk])
                @include('partials.field', ['name' => 'tipe', 'label' => 'Tipe', 'value' => $vehicle->tipe])
                @include('partials.field', ['name' => 'tahun', 'label' => 'Tahun', 'type' => 'number', 'value' => $vehicle->tahun])
                @include('partials.field', ['name' => 'warna', 'label' => 'Warna', 'value' => $vehicle->warna])
                @include('partials.field', ['name' => 'no_rangka', 'label' => 'No. Rangka', 'value' => $vehicle->no_rangka])
                @include('partials.field', ['name' => 'no_mesin', 'label' => 'No. Mesin', 'value' => $vehicle->no_mesin])
                @include('partials.field', ['name' => 'servis_interval_hari', 'label' => 'Interval Servis (hari)', 'type' => 'number', 'value' => $vehicle->servis_interval_hari, 'placeholder' => 'kosong = default ' . \App\Models\Setting::get('servis_interval_hari', '90')])
            </div>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('vehicles.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
