<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\SalesReportService;
use App\Support\OutletContext;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    public function index(Request $request, SalesReportService $service)
    {
        [$from, $to] = $service->resolveRange($request->get('from'), $request->get('to'));
        $outletId = OutletContext::id();
        $summary = $service->salesSummary($outletId, $from, $to);

        return view('report.finance', array_merge(
            compact('summary', 'from', 'to'),
            $service->financeBreakdown($outletId, $from, $to),
        ));
    }
}
