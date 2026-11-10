<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\POS\ShiftService;
use App\Services\Receipt\ReceiptPdfService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();
        $activeShift = $request->user()->activeShift();

        $shifts = Shift::query()
            ->with('user')
            ->withCount('transactions')
            ->where('outlet_id', $outletId)
            ->latest('opened_at')
            ->paginate(15);

        $expectedCash = $activeShift ? app(ShiftService::class)->expectedCash($activeShift) : 0;
        $cashLogs = $activeShift ? $activeShift->cashLogs()->latest('created_at')->get() : collect();
        $shiftTransactions = $activeShift
            ? $activeShift->transactions()->with('payments')->latest('transaction_date')->limit(10)->get()
            : collect();

        return view('shifts.index', compact('activeShift', 'shifts', 'expectedCash', 'cashLogs', 'shiftTransactions'));
    }

    public function open(Request $request, ShiftService $service)
    {
        $data = $request->validate([
            'starting_cash' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $service->open($request->user(), OutletContext::id(), (float) $data['starting_cash']);

            return back()->with('swal_success', 'Shift berhasil dibuka. Selamat bekerja!');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function cashLog(Request $request, ShiftService $service)
    {
        $data = $request->validate([
            'shift_id' => ['required', 'exists:shifts,id'],
            'type' => ['required', 'in:CASH_IN,CASH_OUT'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $shift = Shift::query()->findOrFail($data['shift_id']);
            $service->addCashLog($shift, $data['type'], (float) $data['amount'], $data['reason']);

            return back()->with('swal_success', 'Arus kas shift berhasil dicatat.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function close(Request $request, ShiftService $service, ReceiptPdfService $pdf)
    {
        $data = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $shift = Shift::query()->findOrFail($request->input('shift_id'));
            $service->close($shift, (float) $data['actual_cash'], $data['notes'] ?? null);

            return redirect()->route('shifts.receipt', $shift)
                ->with('swal_success', 'Shift ditutup. Ringkasan shift siap dicetak.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function receipt(Shift $shift, ReceiptPdfService $pdf)
    {
        abort_unless($shift->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);

        return $pdf->shiftSummary($shift);
    }
}
