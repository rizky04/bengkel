@extends('layouts.app')
@section('title', 'Transfer Stok')
@section('header', 'Transfer Stok Antar Cabang')

@section('content')
    <div class="card p-5 max-w-xl">
        <p class="text-sm text-gray-500 mb-4">
            Pindahkan stok dari <strong>cabang aktif</strong> ke cabang lain. Stok berkurang di cabang asal & bertambah di tujuan (tercatat di kartu stok).
        </p>
        <form method="POST" action="{{ route('stock.transfer.store') }}" class="space-y-4" x-data="{ pid: '' }"
              data-confirm="Proses transfer stok?">
            @csrf
            @include('partials.search-select', [
                'name' => 'part_id', 'label' => 'Barang', 'required' => true,
                'placeholder' => '— pilih barang —',
                'options' => $parts->mapWithKeys(fn ($p) => [$p->id => $p->kode.' — '.$p->nama.' (stok '.$p->stok.')']),
            ])
            <div class="grid grid-cols-2 gap-4">
                @include('partials.field', [
                    'name' => 'ke_branch_id', 'label' => 'Cabang Tujuan', 'type' => 'select', 'required' => true,
                    'placeholder' => '— pilih cabang —',
                    'options' => $branches->where('id', '!=', current_branch())->pluck('nama', 'id'),
                ])
                @include('partials.field', ['name' => 'qty', 'label' => 'Jumlah', 'type' => 'number', 'value' => 1, 'required' => true])
            </div>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Transfer</button>
                <a href="{{ route('stock.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
