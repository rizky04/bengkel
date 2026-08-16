@extends('layouts.app')
@section('title', $role->exists ? 'Atur Akses Role' : 'Tambah Role')
@section('header', ($role->exists ? 'Atur Akses — ' . $role->nama : 'Tambah Role'))

@section('content')
    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}"
          x-data="{ full: {{ old('is_admin', $role->is_admin) ? 'true' : 'false' }} }" class="max-w-3xl space-y-4">
        @csrf
        @if ($role->exists) @method('PUT') @endif

        <div class="card p-5">
            <div class="grid md:grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'nama', 'label' => 'Nama Role', 'value' => $role->nama, 'required' => true])
                @if ($role->exists)
                    <div>
                        <label class="form-label">Key</label>
                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm font-mono">{{ $role->key }}</div>
                    </div>
                @else
                    @include('partials.field', ['name' => 'key', 'label' => 'Key (huruf/angka/_)', 'value' => $role->key, 'required' => true, 'placeholder' => 'mis. supervisor'])
                @endif
            </div>

            <label class="flex items-center gap-2 text-sm mt-4">
                <input type="hidden" name="is_admin" value="0">
                <input type="checkbox" name="is_admin" value="1" x-model="full" class="rounded border-gray-300">
                <span class="font-medium text-gray-700">Akses penuh (admin)</span>
                <span class="text-gray-400 text-xs">— melewati semua izin di bawah</span>
            </label>
        </div>

        <div class="card p-5" x-show="!full" x-cloak>
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold text-gray-700">Menu yang Diizinkan</div>
                <div class="text-xs">
                    <button type="button" @click="document.querySelectorAll('.perm-cb').forEach(c=>c.checked=true)" class="text-brand hover:underline">Pilih semua</button>
                    <button type="button" @click="document.querySelectorAll('.perm-cb').forEach(c=>c.checked=false)" class="text-gray-500 hover:underline ml-2">Kosongkan</button>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                @foreach ($grup as $namaGrup => $items)
                    <div>
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $namaGrup }}</div>
                        <div class="space-y-1.5">
                            @foreach ($items as $key => $label)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" class="perm-cb rounded border-gray-300"
                                           @checked(in_array($key, old('permissions', $dipilih)))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('roles.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
