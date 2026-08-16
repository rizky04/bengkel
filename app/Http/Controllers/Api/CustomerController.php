<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = Customer::withCount('vehicles');
        if ($s = $request->get('search')) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('hp', 'like', "%$s%"));
        }

        return $q->orderBy('nama')->limit(300)->get();
    }

    public function store(Request $request)
    {
        return Customer::create($this->validated($request));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return $customer;
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

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
