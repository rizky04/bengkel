<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Service::with('category:id,nama');
        if ($s = $request->get('search')) {
            $q->where('nama', 'like', "%$s%");
        }

        return $q->orderBy('nama')->get();
    }

    public function store(Request $request)
    {
        return Service::create($this->validated($request));
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request));

        return $service;
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return ['ok' => true];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'tarif' => 'required|numeric|min:0',
        ]);
    }
}
