<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = Supplier::query();
        if ($s = $request->get('search')) {
            $q->where('nama', 'like', "%$s%");
        }

        return $q->orderBy('nama')->get();
    }

    public function store(Request $request)
    {
        return Supplier::create($this->validated($request));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return $supplier;
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return ['ok' => true];
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
