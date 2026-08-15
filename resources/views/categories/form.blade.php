@extends('layouts.app')
@section('title', $category->exists ? 'Edit Kategori' : 'Tambah Kategori')
@section('header', ($category->exists ? 'Edit' : 'Tambah') . ' Kategori')

@section('content')
    <div class="card p-5 max-w-md">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="space-y-4">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            @include('partials.field', ['name' => 'nama', 'label' => 'Nama Kategori', 'value' => $category->nama, 'required' => true])
            @include('partials.field', [
                'name' => 'tipe', 'label' => 'Tipe', 'type' => 'select',
                'value' => $category->tipe ?? 'part', 'options' => ['part' => 'Sparepart', 'jasa' => 'Jasa'],
            ])

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
