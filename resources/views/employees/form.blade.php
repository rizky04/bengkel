@extends('layouts.app')
@section('title', $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('header', ($employee->exists ? 'Edit' : 'Tambah') . ' Karyawan')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" class="space-y-4">
            @csrf
            @if ($employee->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'nama', 'label' => 'Nama', 'value' => $employee->nama, 'required' => true])
                @include('partials.field', ['name' => 'jabatan', 'label' => 'Jabatan', 'value' => $employee->jabatan, 'placeholder' => 'mis. Mekanik / Kasir'])
                @include('partials.field', ['name' => 'gaji_pokok', 'label' => 'Gaji Pokok (Rp)', 'type' => 'number', 'value' => $employee->gaji_pokok ?? 0, 'required' => true])
                @include('partials.field', ['name' => 'komisi_persen', 'label' => 'Komisi dari Jasa (%)', 'type' => 'number', 'value' => $employee->komisi_persen])
            </div>
            @include('partials.search-select', ['name' => 'user_id', 'label' => 'Tautkan ke Akun User (untuk komisi mekanik)', 'value' => $employee->user_id, 'placeholder' => '— tidak ditautkan —', 'options' => $users->mapWithKeys(fn($u) => [$u->id => $u->name.' ('.$u->role.')'])])

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $employee->aktif ?? true)) class="rounded border-gray-300"> Karyawan aktif
            </label>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('employees.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
