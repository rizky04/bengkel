<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index()
    {
        return Promo::latest()->limit(100)->get();
    }

    public function store(Request $request)
    {
        return Promo::create($this->validated($request));
    }

    public function update(Request $request, Promo $promo)
    {
        $promo->update($this->validated($request));

        return $promo;
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return ['ok' => true];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'kode' => 'nullable|string|max:50',
            'jenis' => 'required|in:persen,nominal,harga_khusus',
            'nilai' => 'required|numeric|min:0',
            'min_belanja' => 'nullable|numeric|min:0',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'kuota' => 'nullable|integer|min:1',
            'aktif' => 'nullable|boolean',
        ]) + ['aktif' => $request->boolean('aktif')];
    }
}
