<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $services = Service::with('category')
            ->when($q, fn ($query) => $query->where('nama', 'like', "%$q%"))
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('services.index', compact('services', 'q'));
    }

    public function create()
    {
        return view('services.form', $this->formData(new Service));
    }

    public function store(Request $request)
    {
        Service::create($this->validated($request));

        return redirect()->route('services.index')->with('success', 'Jasa ditambahkan.');
    }

    public function edit(Service $service)
    {
        return view('services.form', $this->formData($service));
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request));

        return redirect()->route('services.index')->with('success', 'Jasa diperbarui.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Jasa dihapus.');
    }

    private function formData(Service $service): array
    {
        return ['service' => $service, 'categories' => Category::where('tipe', 'jasa')->orderBy('nama')->get()];
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
