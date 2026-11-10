<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\ExportService;
use App\Services\Report\SalesReportService;
use App\Support\OutletContext;
use Illuminate\Http\Request;

class ExportReportController extends Controller
{
    public function salesExcel(Request $request, SalesReportService $reports, ExportService $export)
    {
        [$from, $to] = $reports->resolveRange($request->get('from'), $request->get('to'));

        return $export->salesExcel($reports->productReport(OutletContext::id(), $from, $to));
    }

    public function salesPdf(Request $request, SalesReportService $reports, ExportService $export)
    {
        [$from, $to] = $reports->resolveRange($request->get('from'), $request->get('to'));
        $outletId = OutletContext::id();

        return $export->salesPdf([
            'summary' => $reports->salesSummary($outletId, $from, $to),
            'products' => $reports->productReport($outletId, $from, $to),
            'from' => $from,
            'to' => $to,
        ], OutletContext::outlet());
    }

    public function financePdf(Request $request, SalesReportService $reports, ExportService $export)
    {
        [$from, $to] = $reports->resolveRange($request->get('from'), $request->get('to'));
        $outletId = OutletContext::id();

        return $export->financePdf(array_merge([
            'summary' => $reports->salesSummary($outletId, $from, $to),
            'from' => $from,
            'to' => $to,
        ], $reports->financeBreakdown($outletId, $from, $to)), OutletContext::outlet());
    }
}
