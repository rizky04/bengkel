<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function index()
    {
        return Branch::withCount('users')->orderBy('nama')->get();
    }

    public function store(Request $request)
    {
        return Branch::create($this->validated($request));
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request));

        return $branch;
    }

    public function destroy(Branch $branch)
    {
        if (Branch::count() <= 1) {
            throw ValidationException::withMessages(['id' => 'Minimal harus ada satu cabang.']);
        }
        $branch->delete();

        return ['ok' => true];
    }

    /** Ganti cabang aktif (admin) — disimpan di sesi token via cache? Tidak: pakai session. */
    public function switch(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);
        session(['branch_id' => (int) $request->branch_id]);

        return ['branch_id' => (int) $request->branch_id];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:30',
            'aktif' => 'nullable|boolean',
        ]) + ['aktif' => $request->boolean('aktif')];
    }
}
