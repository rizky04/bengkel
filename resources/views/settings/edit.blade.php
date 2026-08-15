@extends('layouts.app')
@section('title', 'Pengaturan')
@section('header', 'Pengaturan')

@section('content')
    <div class="grid lg:grid-cols-2 gap-6 items-start">
        {{-- Profil & pajak --}}
        <div class="card p-5">
            <div class="font-semibold text-gray-700 mb-4">Profil Bengkel & Pajak</div>
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
                @csrf @method('PUT')
                @include('partials.field', ['name' => 'nama_bengkel', 'label' => 'Nama Bengkel', 'value' => \App\Models\Setting::get('nama_bengkel'), 'required' => true])
                @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'value' => \App\Models\Setting::get('alamat')])
                <div class="grid grid-cols-2 gap-4">
                    @include('partials.field', ['name' => 'hp', 'label' => 'No. HP', 'value' => \App\Models\Setting::get('hp')])
                    @include('partials.field', ['name' => 'nota_prefix', 'label' => 'Prefix Nota', 'value' => \App\Models\Setting::get('nota_prefix', 'INV'), 'required' => true])
                    @include('partials.field', ['name' => 'nota_lebar', 'label' => 'Lebar Kertas Printer', 'type' => 'select', 'value' => \App\Models\Setting::get('nota_lebar', '58'), 'options' => ['58' => '58mm (mini/Bluetooth)', '80' => '80mm']])
                </div>
                <div class="grid grid-cols-2 gap-4 items-end">
                    <label class="flex items-center gap-2 text-sm pb-2">
                        <input type="hidden" name="pajak_aktif" value="0">
                        <input type="checkbox" name="pajak_aktif" value="1" @checked(\App\Models\Setting::get('pajak_aktif') === '1') class="rounded border-gray-300"> Pajak aktif
                    </label>
                    @include('partials.field', ['name' => 'pajak_persen', 'label' => 'Pajak (%)', 'type' => 'number', 'value' => \App\Models\Setting::get('pajak_persen', '0')])
                </div>
                <button class="btn-primary">Simpan Pengaturan</button>
            </form>
        </div>

        <div class="space-y-6">
            {{-- Platform --}}
            <div class="card p-5">
                <div class="font-semibold text-gray-700 mb-3">Platform / Channel Penjualan</div>
                <form method="POST" action="{{ route('settings.platforms.store') }}" class="flex gap-2 mb-3">
                    @csrf
                    <input name="nama" placeholder="Nama platform baru…" class="form-input" required>
                    <button class="btn-primary btn-sm">Tambah</button>
                </form>
                @foreach ($platforms as $p)
                    <div class="flex items-center justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                        <span>{{ $p->nama }} @unless($p->aktif)<span class="badge badge-gray ml-1">nonaktif</span>@endunless</span>
                        <div class="flex gap-3">
                            <form method="POST" action="{{ route('settings.platforms.toggle', $p) }}">@csrf @method('PATCH')<button class="text-gray-500 hover:underline text-xs">{{ $p->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                            <form method="POST" action="{{ route('settings.platforms.destroy', $p) }}" data-confirm="Hapus platform {{ $p->nama }}?">@csrf @method('DELETE')<button class="text-red-600 hover:underline text-xs">Hapus</button></form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Kategori pengeluaran --}}
            <div class="card p-5">
                <div class="font-semibold text-gray-700 mb-3">Kategori Pengeluaran</div>
                <form method="POST" action="{{ route('settings.cats.store') }}" class="flex gap-2 mb-3">
                    @csrf
                    <input name="nama" placeholder="Kategori baru…" class="form-input" required>
                    <button class="btn-primary btn-sm">Tambah</button>
                </form>
                <div class="flex flex-wrap gap-2">
                    @foreach ($cats as $c)
                        <span class="inline-flex items-center gap-1 badge badge-gray">
                            {{ $c->nama }}
                            <form method="POST" action="{{ route('settings.cats.destroy', $c) }}" data-confirm="Hapus kategori {{ $c->nama }}?" class="inline">@csrf @method('DELETE')<button class="text-red-500 hover:text-red-700">×</button></form>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
