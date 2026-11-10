<?php

namespace App\Services\Receipt;

use App\Models\Shift;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptPdfService
{
    public function receipt(Transaction $transaction, string $paper = '58'): Response
    {
        $transaction->load(['items.modifiers', 'payments', 'user', 'outlet', 'customer', 'shift']);

        $pdf = Pdf::loadView('receipts.receipt-pdf', [
            'transaction' => $transaction,
            'paper' => $paper,
        ]);

        $width = $paper === '80' ? 226.77 : 164.41;
        $pdf->setPaper([0, 0, $width, 620], 'portrait');
        $pdf->setOptions(['isRemoteEnabled' => false, 'defaultFont' => 'sans-serif']);

        return $pdf->stream('struk-'.$transaction->invoice_number.'.pdf');
    }

    public function shiftSummary(Shift $shift): Response
    {
        $shift->load(['user', 'outlet', 'transactions.payments', 'cashLogs']);

        $cashSales = $shift->transactions
            ->whereIn('status', ['PAID', 'PARTIAL_REFUND'])
            ->sum(fn ($trx) => (float) $trx->grand_total);

        $pdf = Pdf::loadView('receipts.shift-pdf', [
            'shift' => $shift,
            'cashSales' => $cashSales,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('shift-'.$shift->id.'.pdf');
    }
}
