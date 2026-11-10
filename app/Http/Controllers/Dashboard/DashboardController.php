<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\Report\SalesReportService;
use App\Support\OutletContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SalesReportService $reports)
    {
        $outletId = OutletContext::id();
        $today = now()->toDateString();
        $summary = $reports->salesSummary($outletId, $today, $today);

        $lowStocks = Stock::query()
            ->with(['variant.product.category'])
            ->where('outlet_id', $outletId)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->orderBy('quantity')
            ->limit(8)
            ->get();

        $lowStockCount = Stock::query()
            ->where('outlet_id', $outletId)
            ->whereColumn('quantity', '<=', 'min_stock')
            ->count();

        $series = $reports->dailySeries($outletId, (int) $request->get('days', 7));
        $topProducts = $reports->topProducts($outletId, 5);
        $payments = $reports->paymentBreakdown($outletId, now()->subDays(30)->toDateString(), $today);

        $recentTransactions = Transaction::query()
            ->with(['user', 'payments'])
            ->where('outlet_id', $outletId)
            ->latest('transaction_date')
            ->limit(6)
            ->get();

        $activeShift = $request->user()->activeShift();

        return view('dashboard.index', [
            'summary' => $summary,
            'todaySales' => $summary['net_sales'],
            'todayTransactions' => $summary['transactions'],
            'todayProfit' => $summary['net_profit'],
            'lowStockCount' => $lowStockCount,
            'lowStocks' => $lowStocks,
            'series' => $series,
            'topProducts' => $topProducts,
            'payments' => $payments,
            'recentTransactions' => $recentTransactions,
            'activeShift' => $activeShift,
            'totalProducts' => ProductVariant::query()->count(),
            'totalCustomers' => Customer::query()->count(),
            'openShifts' => Shift::query()->where('outlet_id', $outletId)->where('status', 'OPEN')->count(),
        ]);
    }
}
