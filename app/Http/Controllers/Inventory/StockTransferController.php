<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\StockTransfer;
use App\Services\Inventory\StockManagerService;
use App\Services\Inventory\StockTransferService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::query()
            ->with(['fromOutlet', 'toOutlet', 'user'])
            ->withCount('details')
            ->latest()
            ->paginate(15);

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $variants = \App\Models\ProductVariant::query()
            ->with(['product', 'stocks' => fn ($q) => $q->where('outlet_id', OutletContext::id())])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->get();

        return view('inventory.transfers.form', compact('outlets', 'variants'));
    }

    public function store(Request $request, StockTransferService $service)
    {
        $data = $request->validate([
            'from_outlet_id' => ['required', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'exists:outlets,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $transfer = $service->create(
                $request->user(),
                (int) $data['from_outlet_id'],
                (int) $data['to_outlet_id'],
                $data['items'],
                $data['notes'] ?? null,
            );

            return redirect()->route('inventory.transfers.index')
                ->with('swal_success', 'Transfer stok '.$transfer->transfer_number.' dibuat. Status: SENT.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function receive(Request $request, StockTransfer $transfer, StockTransferService $service, StockManagerService $stockManager)
    {
        try {
            $service->receive($transfer, $stockManager, $request->user()->id);

            return back()->with('swal_success', 'Transfer diterima dan stok tujuan telah ditambahkan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }
}
