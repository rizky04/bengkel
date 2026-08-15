<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCat;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'periode' => 'required|string|size:7', // YYYY-MM
            'gaji_pokok' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'komisi' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
            'tgl_bayar' => 'required|date',
        ]);

        if ($employee->salaries()->where('periode', $data['periode'])->exists()) {
            return back()->with('error', "Gaji periode {$data['periode']} sudah dibayar.");
        }

        $total = $data['gaji_pokok'] + ($data['bonus'] ?? 0) + ($data['komisi'] ?? 0) - ($data['potongan'] ?? 0);
        if ($total < 0) {
            return back()->with('error', 'Total gaji tidak boleh negatif.');
        }

        DB::transaction(function () use ($employee, $data, $total) {
            // 1) catat pengeluaran kategori Gaji
            $catGaji = ExpenseCat::firstOrCreate(['nama' => 'Gaji']);
            $expense = Expense::create([
                'tanggal' => $data['tgl_bayar'],
                'expense_cat_id' => $catGaji->id,
                'nominal' => $total,
                'keterangan' => "Gaji {$employee->nama} periode {$data['periode']}",
                'metode' => 'tunai',
                'ref_tipe' => 'salary',
                'user_id' => auth()->id(),
            ]);

            // 2) catat slip gaji
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
        \App\Models\ActivityLog::catat('bayar_gaji', "{$employee->nama} • {$request->periode}", 'employee', $employee->id);

        return back()->with('success', "Gaji {$employee->nama} periode {$data['periode']} dibayar & tercatat di pengeluaran.");
    }

    public function destroy(Employee $employee, Salary $salary)
    {
        DB::transaction(function () use ($salary) {
            $salary->expense?->delete();
            $salary->delete();
        });

        return back()->with('success', 'Pembayaran gaji dibatalkan.');
    }
}
