@extends('layouts.app')
@section('title', $service->exists ? 'Edit Jasa' : 'Tambah Jasa')
@section('header', ($service->exists ? 'Edit' : 'Tambah') . ' Jasa')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}" class="space-y-4">
            @csrf
            @if ($service->exists) @method('PUT') @endif

            @include('partials.field', ['name' => 'nama', 'label' => 'Nama Jasa', 'value' => $service->nama, 'required' => true])
            @include('partials.search-select', [
                'name' => 'category_id', 'label' => 'Kategori',
                'value' => $service->category_id, 'placeholder' => '— kategori —',
                'options' => $categories->pluck('nama', 'id'),
            ])
            @include('partials.field', ['name' => 'tarif', 'label' => 'Tarif', 'type' => 'number', 'value' => $service->tarif ?? 0, 'required' => true])

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('services.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
