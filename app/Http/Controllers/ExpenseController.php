<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->get('dari', Carbon::now()->startOfMonth()->toDateString());
        $sampai = $request->get('sampai', Carbon::now()->toDateString());

        $b = current_branch();
        $expenses = Expense::with('category')->where('branch_id', $b)
            ->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->when($request->get('cat'), fn ($q, $v) => $q->where('expense_cat_id', $v))
            ->latest('tanggal')->latest('id')->paginate(20)->withQueryString();

        $total = Expense::where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->when($request->get('cat'), fn ($q, $v) => $q->where('expense_cat_id', $v))->sum('nominal');

        $perKategori = Expense::selectRaw('expense_cat_id, SUM(nominal) as total')
            ->where('branch_id', $b)->whereDate('tanggal', '>=', $dari)->whereDate('tanggal', '<=', $sampai)
            ->groupBy('expense_cat_id')->with('category')->get();

        return view('expenses.index', compact('expenses', 'total', 'perKategori', 'dari', 'sampai')
            + ['cats' => ExpenseCat::orderBy('nama')->get()]);
    }

    public function create()
    {
        return view('expenses.form', ['expense' => new Expense(['tanggal' => now()->toDateString()]), 'cats' => ExpenseCat::orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['bukti'] = $this->simpanBukti($request);
        $data['user_id'] = auth()->id();
        $data['branch_id'] = current_branch();

        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dicatat.');
    }

    public function edit(Expense $expense)
    {
        abort_if($expense->ref_tipe === 'salary', 403, 'Pengeluaran gaji diubah lewat menu Karyawan.');

        return view('expenses.form', ['expense' => $expense, 'cats' => ExpenseCat::orderBy('nama')->get()]);
    }

    public function update(Request $request, Expense $expense)
    {
        abort_if($expense->ref_tipe === 'salary', 403);

        $data = $this->validated($request);
        if ($bukti = $this->simpanBukti($request)) {
            $data['bukti'] = $bukti;
        }
        $expense->update($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        abort_if($expense->ref_tipe === 'salary', 403, 'Hapus lewat menu Karyawan.');
        $expense->delete();

        return back()->with('success', 'Pengeluaran dihapus.');
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

    private function simpanBukti(Request $request): ?string
    {
        if (! $request->hasFile('bukti')) {
            return null;
        }
        $request->validate(['bukti' => 'image|max:2048']);

        return $request->file('bukti')->store('bukti', 'public');
    }
}
