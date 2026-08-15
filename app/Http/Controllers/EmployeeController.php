<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::withSum(['salaries as gaji_bulan_ini' => function ($q) {
            $q->where('periode', now()->format('Y-m'));
        }], 'total_dibayar')->orderBy('nama')->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.form', ['employee' => new Employee(['aktif' => true]), 'users' => $this->linkableUsers()]);
    }

    public function store(Request $request)
    {
        Employee::create($this->validated($request));

        return redirect()->route('employees.index')->with('success', 'Karyawan ditambahkan.');
    }

    public function show(Employee $employee, Request $request)
    {
        $periode = $request->get('periode', now()->format('Y-m'));

        // usulan komisi: persen dari nilai jasa (tx_items tipe jasa) yang dikerjakan mekanik ini di periode
        $komisiUsulan = 0;
        if ($employee->komisi_persen && $employee->user_id) {
            [$y, $m] = explode('-', $periode);
            $nilaiJasa = Transaction::where('mekanik_id', $employee->user_id)
                ->where('status', '!=', 'batal')
                ->whereYear('tgl', $y)->whereMonth('tgl', $m)
                ->join('tx_items', 'tx_items.transaction_id', '=', 'transactions.id')
                ->where('tx_items.tipe', 'jasa')->sum('tx_items.subtotal');
            $komisiUsulan = round($nilaiJasa * $employee->komisi_persen / 100, 2);
        }

        $employee->load(['salaries' => fn ($q) => $q->latest('tgl_bayar')]);
        $sudahDibayar = $employee->salaries->firstWhere('periode', $periode);

        return view('employees.show', compact('employee', 'periode', 'komisiUsulan', 'sudahDibayar'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', compact('employee') + ['users' => $this->linkableUsers($employee)]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request));

        return redirect()->route('employees.index')->with('success', 'Karyawan diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return back()->with('success', 'Karyawan dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'gaji_pokok' => 'required|numeric|min:0',
            'komisi_persen' => 'nullable|numeric|min:0|max:100',
            'user_id' => 'nullable|exists:users,id',
            'aktif' => 'nullable|boolean',
        ]) + ['aktif' => $request->boolean('aktif')];
    }

    private function linkableUsers(?Employee $employee = null)
    {
        return User::orderBy('name')->get(['id', 'name', 'role']);
    }
}
