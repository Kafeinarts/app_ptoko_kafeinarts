<?php

namespace App\Services\Report;

use App\Exports\ProductsExport;
use App\Exports\SalesReportExport;
use App\Models\Outlet;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{
    public function productsExcel(): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'master-produk-'.now()->format('Ymd-His').'.xlsx');
    }

    public function salesExcel(array $rows): BinaryFileResponse
    {
        return Excel::download(new SalesReportExport($rows), 'laporan-penjualan-'.now()->format('Ymd-His').'.xlsx');
    }

    public function salesPdf(array $data, Outlet $outlet)
    {
        $pdf = Pdf::loadView('report.pdf.sales', array_merge($data, ['outlet' => $outlet]));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-penjualan-'.now()->format('Ymd').'.pdf');
    }

    public function financePdf(array $data, Outlet $outlet)
    {
        $pdf = Pdf::loadView('report.pdf.finance', array_merge($data, ['outlet' => $outlet]));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-laba-rugi-'.now()->format('Ymd').'.pdf');
    }

    public function receiptPdf(Transaction $transaction, string $paper = '58')
    {
        return app(\App\Services\Receipt\ReceiptPdfService::class)->receipt($transaction, $paper);
    }
}
