<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCat;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index()
    {
        return Employee::withSum(['salaries as gaji_bulan_ini' => fn ($q) => $q->where('periode', now()->format('Y-m'))], 'total_dibayar')
            ->orderBy('nama')->get();
    }

    public function show(Employee $employee)
    {
        return $employee->load(['salaries' => fn ($q) => $q->latest('tgl_bayar')]);
    }

    public function store(Request $request)
    {
        return Employee::create($this->validated($request));
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request));

        return $employee;
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return ['ok' => true];
    }

    /** Bayar gaji satu periode → tercatat juga di pengeluaran. */
    public function paySalary(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'periode' => 'required|string|size:7',
            'gaji_pokok' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'komisi' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
            'tgl_bayar' => 'required|date',
        ]);
        if ($employee->salaries()->where('periode', $data['periode'])->exists()) {
            throw ValidationException::withMessages(['periode' => "Gaji periode {$data['periode']} sudah dibayar."]);
        }
        $total = $data['gaji_pokok'] + ($data['bonus'] ?? 0) + ($data['komisi'] ?? 0) - ($data['potongan'] ?? 0);
        if ($total < 0) {
            throw ValidationException::withMessages(['potongan' => 'Total gaji tidak boleh negatif.']);
        }

        DB::transaction(function () use ($employee, $data, $total) {
            $catGaji = ExpenseCat::firstOrCreate(['nama' => 'Gaji']);
            $expense = Expense::create([
                'branch_id' => current_branch(),
                'tanggal' => $data['tgl_bayar'],
                'expense_cat_id' => $catGaji->id,
                'nominal' => $total,
                'keterangan' => "Gaji {$employee->nama} periode {$data['periode']}",
                'metode' => 'tunai',
                'ref_tipe' => 'salary',
                'user_id' => auth()->id(),
            ]);
            $salary = $employee->salaries()->create([
                'periode' => $data['periode'],
                'gaji_pokok' => $data['gaji_pokok'],
                'bonus' => $data['bonus'] ?? 0,
                'komisi' => $data['komisi'] ?? 0,
                'potongan' => $data['potongan'] ?? 0,
                'total_dibayar' => $total,
                'tgl_bayar' => $data['tgl_bayar'],
                'expense_id' => $expense->id,
            ]);
            $expense->update(['ref_id' => $salary->id]);
        });
        ActivityLog::catat('bayar_gaji', "{$employee->nama} • {$data['periode']}", 'employee', $employee->id);

        return $this->show($employee->fresh());
    }

    public function destroySalary(Employee $employee, Salary $salary)
    {
        DB::transaction(function () use ($salary) {
            $salary->expense?->delete();
            $salary->delete();
        });

        return ['ok' => true];
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
}
