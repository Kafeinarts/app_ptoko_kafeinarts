<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Inventory\StockManagerService;
use App\Services\Purchasing\PurchaseService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'user'])
            ->withCount('items')
            ->where('outlet_id', OutletContext::id())
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('q'), fn ($q, $v) => $q->where('po_number', 'like', "%$v%"))
            ->latest('po_date')
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Supplier::query()->orderBy('name')->get();
        $variants = ProductVariant::query()
            ->with(['product', 'prices' => fn ($q) => $q->where('outlet_id', OutletContext::id())])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get();

        return view('purchasing.form', compact('suppliers', 'variants'));
    }

    public function store(Request $request, PurchaseService $service)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $po = $service->create(
                $request->user(),
                OutletContext::id(),
                (int) $data['supplier_id'],
                $data['items'],
                $data['notes'] ?? null,
            );

            return redirect()->route('purchasing.show', $po)->with('swal_success', 'Purchase Order '.$po->po_number.' berhasil dibuat.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchase)
    {
        abort_unless($purchase->outlet_id === OutletContext::id() || auth()->user()->isSuperAdmin(), 403);
        $purchase->load(['items.variant.product', 'supplier', 'user', 'returns.items']);

        return view('purchasing.show', ['order' => $purchase]);
    }

    public function receive(Request $request, PurchaseOrder $purchase, PurchaseService $service, StockManagerService $stockManager)
    {
        try {
            $service->receive($purchase, $request->user(), $stockManager);

            return back()->with('swal_success', 'Barang diterima. Stok inventaris telah ditambahkan otomatis.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchase, PurchaseService $service)
    {
        try {
            $service->cancel($purchase);

            return back()->with('swal_success', 'Purchase Order dibatalkan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function returnStore(Request $request, PurchaseOrder $purchase, PurchaseService $service, StockManagerService $stockManager)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $service->createReturn($purchase, $request->user(), $data['items'], $data['reason'] ?? null, $stockManager);

            return back()->with('swal_success', 'Retur pembelian berhasil dicatat dan stok dikurangi.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }
}
