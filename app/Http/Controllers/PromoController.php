<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index()
    {
        $promos = Promo::latest()->paginate(15);

        return view('promos.index', compact('promos'));
    }

    public function create()
    {
        return view('promos.form', ['promo' => new Promo(['jenis' => 'persen', 'aktif' => true])]);
    }

    public function store(Request $request)
    {
        Promo::create($this->validated($request));

        return redirect()->route('promos.index')->with('success', 'Promo dibuat.');
    }

    public function edit(Promo $promo)
    {
        return view('promos.form', compact('promo'));
    }

    public function update(Request $request, Promo $promo)
    {
        $promo->update($this->validated($request));

        return redirect()->route('promos.index')->with('success', 'Promo diperbarui.');
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return back()->with('success', 'Promo dihapus.');
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
