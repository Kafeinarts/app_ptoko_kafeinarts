<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockLog;
use App\Services\Inventory\StockManagerService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();

        $stocks = Stock::query()
            ->with(['variant.product.category'])
            ->where('outlet_id', $outletId)
            ->when($request->get('q'), fn ($q, $v) => $q->whereHas('variant.product', fn ($qq) => $qq
                ->where('name', 'like', "%$v%")->orWhere('sku', 'like', "%$v%")))
            ->when($request->get('filter') === 'low', fn ($q) => $q->whereColumn('quantity', '<=', 'min_stock'))
            ->orderBy('quantity')
            ->paginate(20)
            ->withQueryString();

        $logs = StockLog::query()
            ->with(['variant.product', 'user'])
            ->where('outlet_id', $outletId)
            ->when($request->get('type'), fn ($q, $v) => $q->where('type', $v))
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('inventory.stocks', compact('stocks', 'logs'));
    }

    public function create()
    {
        $variants = \App\Models\ProductVariant::query()
            ->with([
                'product',
                'prices' => fn ($q) => $q->where('outlet_id', OutletContext::id()),
                'stocks' => fn ($q) => $q->where('outlet_id', OutletContext::id()),
            ])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($v) => $v->display_name)
            ->values();

        return view('inventory.adjust', compact('variants'));
    }

    public function store(Request $request, StockManagerService $service)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'type' => ['required', 'in:IN,OUT,ADJUSTMENT,RETURN'],
            'qty' => ['required', 'integer', 'min:1'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $change = in_array($data['type'], ['OUT'], true) ? -abs($data['qty']) : abs($data['qty']);

        try {
            $service->adjust(
                OutletContext::id(),
                (int) $data['variant_id'],
                $change,
                $data['type'],
                null,
                $data['notes'],
                $request->user()->id,
                isset($data['min_stock']) ? (int) $data['min_stock'] : null,
            );

            return redirect()->route('inventory.stocks')->with('swal_success', 'Mutasi stok berhasil dicatat.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }
}
