<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\POS\ReturnService;
use App\Services\Receipt\ReceiptPdfService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'in:PAID,VOID,REFUND,PARTIAL_REFUND'],
            'q' => ['nullable', 'string', 'max:100'],
            'cashier' => ['nullable', 'integer'],
        ]);

        $transactions = Transaction::query()
            ->with(['user', 'customer', 'payments'])
            ->where('outlet_id', OutletContext::id())
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['cashier'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('invoice_number', 'like', '%'.$v.'%'))
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString();

        $cashiers = User::query()->orderBy('name')->get();

        return view('pos.history', compact('transactions', 'cashiers', 'filters'));
    }

    public function show(Transaction $transaction)
    {
        abort_unless($transaction->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        return response()->json($transaction->load(['items.variant.product', 'items.modifiers', 'payments', 'customer', 'user']));
    }

    public function receipt(Transaction $transaction, ReceiptPdfService $pdf, Request $request)
    {
        abort_unless($transaction->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        return $pdf->receipt($transaction, $request->get('paper', '58'));
    }

    public function void(Request $request, Transaction $transaction, ReturnService $service)
    {
        abort_unless($transaction->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'approved_by' => ['required', 'exists:users,id'],
        ]);

        try {
            $approver = User::query()->findOrFail($data['approved_by']);
            $service->void($transaction, $request->user(), $approver, $data['reason']);

            return back()->with('swal_success', 'Transaksi berhasil di-void dan stok telah dikembalikan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function returnForm(Transaction $transaction)
    {
        abort_unless($transaction->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);
        $transaction->load('items.variant.product', 'returns.items');

        return view('pos.return', compact('transaction'));
    }

    public function returnStore(Request $request, Transaction $transaction, ReturnService $service)
    {
        abort_unless($transaction->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array'],
            'items.*.item_id' => ['required', 'exists:transaction_items,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $service->returnItems($transaction, $request->user(), $data['items'], $data['reason'] ?? null);

            return redirect()->route('pos.history')->with('swal_success', 'Retur penjualan berhasil diproses dan stok dikembalikan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage())->withInput();
        }
    }
}
