<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $customers = Customer::when($q, fn ($query) => $query
                ->where('nama', 'like', "%$q%")->orWhere('hp', 'like', "%$q%"))
            ->withCount('vehicles')->latest()->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }

    public function export()
    {
        $rows = Customer::withCount('vehicles')->orderBy('nama')->get()
            ->map(fn ($c) => [$c->nama, $c->hp, $c->alamat, $c->vehicles_count]);

        return csv_download('pelanggan-' . now()->format('Ymd') . '.csv',
            ['nama', 'hp', 'alamat', 'jumlah_kendaraan'], $rows);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request)
    {
        Customer::create($this->validated($request));

        return redirect()->route('customers.index')->with('success', 'Pelanggan ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $customer->load('vehicles');

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return redirect()->route('customers.index')->with('success', 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('success', 'Pelanggan dihapus.');
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
