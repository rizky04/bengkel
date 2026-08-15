<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('tipe')->orderBy('nama')->get()->groupBy('tipe');

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Kategori ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:part,jasa',
        ]);
    }
}
