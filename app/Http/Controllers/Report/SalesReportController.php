<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\SalesReportService;
use App\Support\OutletContext;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesReportService $service)
    {
        [$from, $to] = $service->resolveRange($request->get('from'), $request->get('to'));
        $outletId = OutletContext::id();

        $summary = $service->salesSummary($outletId, $from, $to);
        $products = $service->productReport($outletId, $from, $to);
        $categories = $service->categorySales($outletId, $from, $to);
        $series = $service->dailySeries($outletId, 30);
        $topProducts = $service->topProducts($outletId, 8, $from, $to);
        $payments = $service->paymentBreakdown($outletId, $from, $to);

        return view('report.sales', compact(
            'summary', 'products', 'categories', 'series', 'topProducts', 'payments', 'from', 'to'
        ));
    }
}
