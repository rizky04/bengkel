<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $vehicles = Vehicle::with('customer')
            ->when($q, fn ($query) => $query
                ->where('plat', 'like', "%$q%")->orWhere('merk', 'like', "%$q%")->orWhere('tipe', 'like', "%$q%"))
            ->latest()->paginate(15)->withQueryString();

        return view('vehicles.index', compact('vehicles', 'q'));
    }

    public function create(Request $request)
    {
        $vehicle = new Vehicle(['customer_id' => $request->get('customer_id')]);

        return view('vehicles.form', ['vehicle' => $vehicle, 'customers' => Customer::orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        Vehicle::create($this->validated($request));

        return redirect()->route('vehicles.index')->with('success', 'Kendaraan ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.form', ['vehicle' => $vehicle, 'customers' => Customer::orderBy('nama')->get()]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $vehicle->update($this->validated($request));

        return redirect()->route('vehicles.index')->with('success', 'Kendaraan diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return back()->with('success', 'Kendaraan dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plat' => 'required|string|max:20',
            'jenis' => 'required|in:motor,mobil',
            'merk' => 'nullable|string|max:100',
            'tipe' => 'nullable|string|max:100',
            'tahun' => 'nullable|integer|min:1950|max:2100',
            'no_rangka' => 'nullable|string|max:100',
            'no_mesin' => 'nullable|string|max:100',
            'warna' => 'nullable|string|max:50',
        ]);
    }
}
