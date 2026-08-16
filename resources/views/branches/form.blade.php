@extends('layouts.app')
@section('title', $branch->exists ? 'Edit Cabang' : 'Tambah Cabang')
@section('header', ($branch->exists ? 'Edit' : 'Tambah') . ' Cabang')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" class="space-y-4">
            @csrf
            @if ($branch->exists) @method('PUT') @endif

            @include('partials.field', ['name' => 'nama', 'label' => 'Nama Cabang', 'value' => $branch->nama, 'required' => true])
            @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'value' => $branch->alamat])
            @include('partials.field', ['name' => 'hp', 'label' => 'No. HP', 'value' => $branch->hp])

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $branch->aktif ?? true)) class="rounded border-gray-300"> Cabang aktif
            </label>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('branches.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
