<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->get('dari', Carbon::now()->startOfMonth()->toDateString());
        $sampai = $request->get('sampai', Carbon::now()->toDateString());
        $b = current_branch();

        $base = fn () => Expense::where('branch_id', $b)
            ->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->when($request->get('cat'), fn ($q, $v) => $q->where('expense_cat_id', $v));

        return [
            'data' => $base()->with('category:id,nama')->latest('tanggal')->latest('id')->limit(200)->get(),
            'total' => (float) $base()->sum('nominal'),
            'dari' => $dari,
            'sampai' => $sampai,
            'categories' => ExpenseCat::orderBy('nama')->get(),
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();
        $data['branch_id'] = current_branch();

        return Expense::create($data)->load('category:id,nama');
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->ref_tipe === 'salary') {
            throw ValidationException::withMessages(['nominal' => 'Pengeluaran gaji diubah lewat menu Karyawan.']);
        }
        $expense->update($this->validated($request));

        return $expense->load('category:id,nama');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->ref_tipe === 'salary') {
            throw ValidationException::withMessages(['id' => 'Hapus lewat menu Karyawan.']);
        }
        $expense->delete();

        return ['ok' => true];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tanggal' => 'required|date',
            'expense_cat_id' => 'nullable|exists:expense_cats,id',
            'nominal' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:255',
            'metode' => 'required|in:tunai,transfer,qris,kartu',
        ]);
    }
}
