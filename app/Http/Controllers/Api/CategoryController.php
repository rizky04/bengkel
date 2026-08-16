<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = Category::query();
        if ($tipe = $request->get('tipe')) {
            $q->where('tipe', $tipe);
        }

        return $q->orderBy('tipe')->orderBy('nama')->get();
    }

    public function store(Request $request)
    {
        return Category::create($this->validated($request));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));

        return $category;
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return ['ok' => true];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:part,jasa',
        ]);
    }
}
