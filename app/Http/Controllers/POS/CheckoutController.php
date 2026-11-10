<?php

namespace App\Http\Controllers\POS;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\POS\StoreCheckoutRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\POS\CheckoutService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Throwable;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $outletId = OutletContext::id();

        $products = Product::query()
            ->with([
                'category',
                'variants.prices' => fn ($q) => $q->where('outlet_id', $outletId),
                'variants.stocks' => fn ($q) => $q->where('outlet_id', $outletId),
                'modifierGroups.modifiers',
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();
        $vouchers = Voucher::query()->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->get();

        $activeShift = $request->user()->activeShift();

        return view('pos.index', compact('products', 'categories', 'customers', 'vouchers', 'activeShift', 'outletId'));
    }

    public function store(StoreCheckoutRequest $request, CheckoutService $service)
    {
        try {
            $transaction = $service->processCheckout(
                $request->validated(),
                $request->user(),
                OutletContext::id(),
            );

            return ResponseHelper::success('Transaksi berhasil diproses.', [
                'transaction' => $transaction,
                'receipt_url' => route('pos.receipt', $transaction),
            ]);
        } catch (Throwable $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
