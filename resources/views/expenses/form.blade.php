@extends('layouts.app')
@section('title', $expense->exists ? 'Edit Pengeluaran' : 'Tambah Pengeluaran')
@section('header', ($expense->exists ? 'Edit' : 'Tambah') . ' Pengeluaran')

@section('content')
    <div class="card p-5 max-w-xl">
        <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($expense->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', ['name' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date', 'value' => optional($expense->tanggal)->format('Y-m-d') ?? $expense->tanggal, 'required' => true])
                @include('partials.search-select', ['name' => 'expense_cat_id', 'label' => 'Kategori', 'value' => $expense->expense_cat_id, 'placeholder' => '— pilih kategori —', 'options' => $cats->pluck('nama', 'id')])
                @include('partials.field', ['name' => 'nominal', 'label' => 'Nominal (Rp)', 'type' => 'number', 'value' => $expense->nominal, 'required' => true])
                @include('partials.field', ['name' => 'metode', 'label' => 'Metode', 'type' => 'select', 'value' => $expense->metode ?? 'tunai', 'options' => ['tunai'=>'Tunai','transfer'=>'Transfer','qris'=>'QRIS','kartu'=>'Kartu']])
            </div>
            @include('partials.field', ['name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea', 'value' => $expense->keterangan])

            <div>
                <label class="form-label">Bukti (gambar, opsional)</label>
                <input type="file" name="bukti" accept="image/*" class="form-input">
                @if ($expense->bukti)<a href="{{ asset('storage/'.$expense->bukti) }}" target="_blank" class="text-xs text-brand hover:underline">bukti saat ini</a>@endif
            </div>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('expenses.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
