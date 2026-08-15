@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('header', 'Detail Pelanggan')

@section('content')
    <div class="grid md:grid-cols-3 gap-6">
        <div class="card md:col-span-1">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Data Pelanggan</div>
            <div class="p-5">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">Nama</dt><dd class="font-medium">{{ $customer->nama }}</dd></div>
                    <div><dt class="text-gray-500">HP</dt><dd>{{ $customer->hp ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Alamat</dt><dd>{{ $customer->alamat ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Catatan</dt><dd>{{ $customer->catatan ?: '-' }}</dd></div>
                </dl>
                <a href="{{ route('customers.edit', $customer) }}" class="inline-block mt-4 text-brand text-sm hover:underline">Edit</a>
            </div>
        </div>

        <div class="card md:col-span-2">
            <div class="px-5 py-3 border-b font-semibold text-gray-700">Kendaraan</div>
            <div class="p-5">
                <div class="flex justify-end mb-3">
                    <a href="{{ route('vehicles.create', ['customer_id' => $customer->id]) }}"
                       class="px-3 py-1.5 bg-brand text-white rounded-lg text-sm">+ Kendaraan</a>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr><th class="py-2">Plat</th><th>Jenis</th><th>Merk/Tipe</th><th>Tahun</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($customer->vehicles as $v)
                            <tr>
                                <td class="py-2 font-medium">{{ $v->plat }}</td>
                                <td class="capitalize">{{ $v->jenis }}</td>
                                <td>{{ $v->merk }} {{ $v->tipe }}</td>
                                <td>{{ $v->tahun }}</td>
                                <td class="text-right"><a href="{{ route('vehicles.edit', $v) }}" class="text-brand hover:underline">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-400">Belum ada kendaraan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
