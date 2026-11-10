<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $incomes = Income::query()
            ->with(['category', 'user'])
            ->where('outlet_id', $outletId)
            ->whereBetween('income_date', [$from, $to])
            ->latest('income_date')
            ->paginate(20)
            ->withQueryString();

        $total = Income::query()->where('outlet_id', $outletId)->whereBetween('income_date', [$from, $to])->sum('amount');
        $categories = IncomeCategory::query()->orderBy('name')->get();

        return view('finance.incomes', compact('incomes', 'categories', 'total', 'from', 'to'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'income_category_id' => ['required', 'exists:income_categories,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'income_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            Income::query()->create($data + [
                'outlet_id' => OutletContext::id(),
                'user_id' => $request->user()->id,
            ]);

            return back()->with('swal_success', 'Pemasukan berhasil dicatat.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:income_categories,name']]);
        IncomeCategory::query()->create($data);

        return back()->with('swal_success', 'Kategori pemasukan ditambahkan.');
    }

    public function destroy(Income $income)
    {
        abort_unless($income->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        try {
            $income->delete();

            return back()->with('swal_success', 'Pemasukan dihapus.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Pemasukan tidak dapat dihapus.');
        }
    }
}
