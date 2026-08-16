<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $q = Vehicle::with('customer:id,nama');
        if ($cid = $request->get('customer_id')) {
            $q->where('customer_id', $cid);
        }
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('plat', 'like', "%$s%")->orWhere('merk', 'like', "%$s%")->orWhere('tipe', 'like', "%$s%"));
        }

        return $q->orderBy('id', 'desc')->limit(300)->get();
    }

    public function store(Request $request)
    {
        return Vehicle::create($this->validated($request))->load('customer:id,nama');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $vehicle->update($this->validated($request));

        return $vehicle->load('customer:id,nama');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return ['ok' => true];
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
            'servis_interval_hari' => 'nullable|integer|min:1|max:1000',
        ]);
    }
}
