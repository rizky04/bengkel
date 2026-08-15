@extends('layouts.app')
@section('title', $promo->exists ? 'Edit Promo' : 'Tambah Promo')
@section('header', ($promo->exists ? 'Edit' : 'Tambah') . ' Promo')

@section('content')
    <div class="card p-5 max-w-2xl">
        <form method="POST" action="{{ $promo->exists ? route('promos.update', $promo) : route('promos.store') }}" class="space-y-4">
            @csrf
            @if ($promo->exists) @method('PUT') @endif

            <div class="grid md:grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'nama', 'label' => 'Nama Promo', 'value' => $promo->nama, 'required' => true])
                @include('partials.field', ['name' => 'kode', 'label' => 'Kode Voucher (opsional)', 'value' => $promo->kode])
                @include('partials.field', [
                    'name' => 'jenis', 'label' => 'Jenis Diskon', 'type' => 'select', 'value' => $promo->jenis,
                    'options' => ['persen' => 'Persen (%)', 'nominal' => 'Potongan Nominal (Rp)', 'harga_khusus' => 'Harga Khusus (jadi Rp)'],
                ])
                @include('partials.field', ['name' => 'nilai', 'label' => 'Nilai', 'type' => 'number', 'value' => $promo->nilai, 'required' => true])
                @include('partials.field', ['name' => 'min_belanja', 'label' => 'Min. Belanja (Rp)', 'type' => 'number', 'value' => $promo->min_belanja])
                @include('partials.field', ['name' => 'kuota', 'label' => 'Kuota Pemakaian (opsional)', 'type' => 'number', 'value' => $promo->kuota])
                @include('partials.field', ['name' => 'mulai', 'label' => 'Mulai', 'type' => 'date', 'value' => $promo->mulai?->format('Y-m-d')])
                @include('partials.field', ['name' => 'selesai', 'label' => 'Selesai', 'type' => 'date', 'value' => $promo->selesai?->format('Y-m-d')])
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $promo->aktif ?? true)) class="rounded border-gray-300">
                Promo aktif
            </label>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('promos.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
