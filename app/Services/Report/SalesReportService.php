<?php

namespace App\Services\Report;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function salesSummary(int $outletId, string $from, string $to): array
    {
        $base = Transaction::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['PAID', 'PARTIAL_REFUND'])
            ->whereBetween('transaction_date', [$from.' 00:00:00', $to.' 23:59:59']);

        $aggregate = (clone $base)->selectRaw('
            COALESCE(SUM(subtotal), 0) as subtotal,
            COALESCE(SUM(discount_amount), 0) as discount,
            COALESCE(SUM(tax_amount), 0) as tax,
            COALESCE(SUM(grand_total), 0) as total,
            COUNT(*) as transactions
        ')->first();

        $cogs = (float) TransactionItem::query()
            ->whereHas('transaction', fn ($q) => $q
                ->where('outlet_id', $outletId)
                ->whereIn('status', ['PAID', 'PARTIAL_REFUND'])
                ->whereBetween('transaction_date', [$from.' 00:00:00', $to.' 23:59:59']))
            ->selectRaw('COALESCE(SUM(cost_price * qty), 0) as cogs')
            ->value('cogs');

        $expenses = (float) Expense::query()
            ->where('outlet_id', $outletId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $incomes = (float) Income::query()
            ->where('outlet_id', $outletId)
            ->whereBetween('income_date', [$from, $to])
            ->sum('amount');

        $netSales = (float) ($aggregate->total ?? 0);
        $grossProfit = $netSales - (float) $cogs;

        return [
            'subtotal' => (float) ($aggregate->subtotal ?? 0),
            'discount' => (float) ($aggregate->discount ?? 0),
            'tax' => (float) ($aggregate->tax ?? 0),
            'net_sales' => $netSales,
            'transactions' => (int) ($aggregate->transactions ?? 0),
            'cogs' => (float) $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'incomes' => $incomes,
            'net_profit' => $grossProfit + $incomes - $expenses,
            'average_ticket' => ($aggregate->transactions ?? 0) > 0 ? $netSales / $aggregate->transactions : 0,
        ];
    }

    /**
     * @return array{labels:array<int,string>, sales:array<int,float>, transactions:array<int,int>}
     */
    public function dailySeries(int $outletId, int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Transaction::query()
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['PAID', 'PARTIAL_REFUND'])
            ->where('transaction_date', '>=', $start)
            ->selectRaw('DATE(transaction_date) as date, SUM(grand_total) as total, COUNT(*) as count')
            ->groupByRaw('DATE(transaction_date)')
            ->get()
            ->keyBy('date');

        $labels = [];
        $sales = [];
        $transactions = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $sales[] = (float) ($rows[$key]->total ?? 0);
            $transactions[] = (int) ($rows[$key]->count ?? 0);
        }

        return compact('labels', 'sales', 'transactions');
    }

    public function topProducts(int $outletId, int $limit = 5, ?string $from = null, ?string $to = null): array
    {
        $query = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('product_variants', 'product_variants.id', '=', 'transaction_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('transactions.outlet_id', $outletId)
            ->whereIn('transactions.status', ['PAID', 'PARTIAL_REFUND'])
            ->selectRaw('products.name as product_name, SUM(transaction_items.qty) as qty, SUM(transaction_items.total) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->limit($limit);

        if ($from && $to) {
            $query->whereBetween('transactions.transaction_date', [$from.' 00:00:00', $to.' 23:59:59']);
        }

        return $query->get()->map(fn ($row) => [
            'name' => $row->product_name,
            'qty' => (int) $row->qty,
            'revenue' => (float) $row->revenue,
        ])->all();
    }

    public function paymentBreakdown(int $outletId, ?string $from = null, ?string $to = null): array
    {
        $query = TransactionPayment::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.outlet_id', $outletId)
            ->whereIn('transactions.status', ['PAID', 'PARTIAL_REFUND'])
            ->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
            ->groupBy('payment_method');

        if ($from && $to) {
            $query->whereBetween('transactions.transaction_date', [$from.' 00:00:00', $to.' 23:59:59']);
        }

        return $query->get()->map(fn ($row) => [
            'method' => $row->payment_method,
            'total' => (float) $row->total,
            'count' => (int) $row->count,
        ])->all();
    }

    public function categorySales(int $outletId, string $from, string $to): array
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('product_variants', 'product_variants.id', '=', 'transaction_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.outlet_id', $outletId)
            ->whereIn('transactions.status', ['PAID', 'PARTIAL_REFUND'])
            ->whereBetween('transactions.transaction_date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('categories.name as category, SUM(transaction_items.qty) as qty, SUM(transaction_items.total) as revenue')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'qty' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
            ])->all();
    }

    public function productReport(int $outletId, string $from, string $to): array
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('product_variants', 'product_variants.id', '=', 'transaction_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.outlet_id', $outletId)
            ->whereIn('transactions.status', ['PAID', 'PARTIAL_REFUND'])
            ->whereBetween('transactions.transaction_date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('
                products.name as product,
                product_variants.variant_name,
                categories.name as category,
                SUM(transaction_items.qty) as qty,
                SUM(transaction_items.total) as revenue,
                SUM(transaction_items.cost_price * transaction_items.qty) as cogs
            ')
            ->groupBy('products.id', 'products.name', 'product_variants.variant_name', 'categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product,
                'variant' => $row->variant_name,
                'category' => $row->category,
                'qty' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
                'cogs' => (float) $row->cogs,
                'margin' => (float) $row->revenue - (float) $row->cogs,
            ])->all();
    }

    public function financeBreakdown(int $outletId, string $from, string $to): array
    {
        $expensesByCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.outlet_id', $outletId)
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        $incomesByCategory = Income::query()
            ->join('income_categories', 'income_categories.id', '=', 'incomes.income_category_id')
            ->where('incomes.outlet_id', $outletId)
            ->whereBetween('income_date', [$from, $to])
            ->selectRaw('income_categories.name as category, SUM(incomes.amount) as total')
            ->groupBy('income_categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'expensesByCategory' => $expensesByCategory,
            'incomesByCategory' => $incomesByCategory,
        ];
    }

    public function resolveRange(?string $from, ?string $to): array
    {
        $from = $from ? Carbon::parse($from)->toDateString() : now()->startOfMonth()->toDateString();
        $to = $to ? Carbon::parse($to)->toDateString() : now()->toDateString();

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
