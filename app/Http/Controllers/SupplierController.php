<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $suppliers = Supplier::when($q, fn ($query) => $query->where('nama', 'like', "%$q%"))
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('suppliers.index', compact('suppliers', 'q'));
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
    }
}
