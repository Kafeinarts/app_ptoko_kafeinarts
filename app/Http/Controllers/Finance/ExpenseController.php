<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();
        [$from, $to] = $this->range($request);

        $expenses = Expense::query()
            ->with(['category', 'user'])
            ->where('outlet_id', $outletId)
            ->whereBetween('expense_date', [$from, $to])
            ->latest('expense_date')
            ->paginate(20)
            ->withQueryString();

        $total = Expense::query()->where('outlet_id', $outletId)->whereBetween('expense_date', [$from, $to])->sum('amount');
        $categories = ExpenseCategory::query()->orderBy('name')->get();

        return view('finance.expenses', compact('expenses', 'categories', 'total', 'from', 'to'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            Expense::query()->create($data + [
                'outlet_id' => OutletContext::id(),
                'user_id' => $request->user()->id,
            ]);

            return back()->with('swal_success', 'Pengeluaran berhasil dicatat.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:expense_categories,name']]);
        ExpenseCategory::query()->create($data);

        return back()->with('swal_success', 'Kategori pengeluaran ditambahkan.');
    }

    public function destroy(Expense $expense)
    {
        abort_unless($expense->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        try {
            $expense->delete();

            return back()->with('swal_success', 'Pengeluaran dihapus.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Pengeluaran tidak dapat dihapus.');
        }
    }

    protected function range(Request $request): array
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return [$from, $to];
    }
}
