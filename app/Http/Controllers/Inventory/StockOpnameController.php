<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Services\Inventory\StockOpnameService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class StockOpnameController extends Controller
{
    public function index()
    {
        $opnames = StockOpname::query()
            ->with('user')
            ->withCount('details')
            ->where('outlet_id', OutletContext::id())
            ->latest('opname_date')
            ->paginate(15);

        return view('inventory.opnames.index', compact('opnames'));
    }

    public function create()
    {
        $stocks = Stock::query()
            ->with(['variant.product'])
            ->where('outlet_id', OutletContext::id())
            ->get()
            ->sortBy(fn ($s) => $s->variant?->display_name);

        return view('inventory.opnames.form', compact('stocks'));
    }

    public function store(Request $request, StockOpnameService $service)
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.physical_qty' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $opname = $service->create($request->user(), OutletContext::id(), $data['items'], $data['notes'] ?? null);

            return redirect()->route('inventory.opnames.show', $opname)
                ->with('swal_success', 'Stock opname berhasil disimpan dan stok telah disesuaikan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function show(StockOpname $opname)
    {
        abort_unless($opname->outlet_id === OutletContext::id(), 403);

        $opname->load('details.variant.product', 'user', 'outlet');

        return view('inventory.opnames.show', compact('opname'));
    }
}
