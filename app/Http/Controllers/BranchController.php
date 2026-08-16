<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('users')->orderBy('nama')->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.form', ['branch' => new Branch(['aktif' => true])]);
    }

    public function store(Request $request)
    {
        Branch::create($this->validated($request));

        return redirect()->route('branches.index')->with('success', 'Cabang ditambahkan.');
    }

    public function edit(Branch $branch)
    {
        return view('branches.form', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request));

        return redirect()->route('branches.index')->with('success', 'Cabang diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        if (Branch::count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu cabang.');
        }
        $branch->delete();

        return back()->with('success', 'Cabang dihapus.');
    }

    /** Ganti cabang aktif (admin) — disimpan di sesi. */
    public function switch(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);
        session(['branch_id' => (int) $request->branch_id]);

        return back();
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
