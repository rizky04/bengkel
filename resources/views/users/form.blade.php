@extends('layouts.app')
@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('header', ($user->exists ? 'Edit' : 'Tambah') . ' Pengguna')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-4">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'name', 'label' => 'Nama', 'value' => $user->name, 'required' => true])
                @include('partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $user->email, 'required' => true])
                @include('partials.field', ['name' => 'password', 'label' => $user->exists ? 'Password (kosongkan jika tidak diubah)' : 'Password', 'type' => 'password', 'required' => ! $user->exists])
                @include('partials.field', ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'value' => $user->role ?? 'kasir', 'options' => $roles->pluck('nama', 'key')])
                @include('partials.field', ['name' => 'branch_id', 'label' => 'Cabang', 'type' => 'select', 'value' => $user->branch_id, 'placeholder' => '— pilih cabang —', 'options' => $branches->pluck('nama', 'id')])
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $user->aktif ?? true)) class="rounded border-gray-300"> Akun aktif
            </label>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
