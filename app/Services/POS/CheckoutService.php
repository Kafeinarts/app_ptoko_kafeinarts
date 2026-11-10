<?php

namespace App\Services\POS;

use App\Helpers\InvoiceNumberHelper;
use App\Models\Customer;
use App\Models\ProductModifier;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CheckoutService
{
    public function __construct(private readonly LoyaltyPointService $loyaltyPointService) {}

    /**
     * Process a POS checkout safely inside a database transaction.
     *
     * @param  array{items: array<int, array{variant_id:int, qty:int, discount?:float, notes?:string, modifiers?:array<int,int>}>, payment_method:string, amount_paid:float, discount_amount?:float, voucher_code?:string, customer_id?:int, points_used?:int, notes?:string}  $data
     */
    public function processCheckout(array $data, User $user, int $outletId): Transaction
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Keranjang belanja masih kosong.');
        }

        return DB::transaction(function () use ($data, $user, $outletId) {
            $shift = Shift::query()
                ->where('outlet_id', $outletId)
                ->where('user_id', $user->id)
                ->where('status', 'OPEN')
                ->lockForUpdate()
                ->latest('opened_at')
                ->first();

            if (! $shift) {
                throw new RuntimeException('Shift kasir belum dibuka. Silakan buka shift terlebih dahulu.');
            }

            $customer = isset($data['customer_id'])
                ? Customer::query()->find($data['customer_id'])
                : null;

            $subtotal = 0.0;
            $itemDiscountTotal = 0.0;
            $preparedItems = [];

            foreach ($data['items'] as $row) {
                $variant = ProductVariant::query()->with('product')->findOrFail($row['variant_id']);
                $qty = max(1, (int) $row['qty']);

                $price = ProductPrice::query()
                    ->where('product_variant_id', $variant->id)
                    ->where('outlet_id', $outletId)
                    ->first()
                    ?? ProductPrice::query()->where('product_variant_id', $variant->id)->first();

                if (! $price) {
                    throw new RuntimeException("Harga produk {$variant->display_name} belum diatur.");
                }

                $stock = Stock::query()
                    ->where('outlet_id', $outletId)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->quantity < $qty) {
                    throw new RuntimeException("Stok {$variant->display_name} tidak mencukupi (tersedia ".($stock->quantity ?? 0).').');
                }

                $modifierIds = array_filter(array_map('intval', $row['modifiers'] ?? []));
                $modifiers = $modifierIds
                    ? ProductModifier::query()
                        ->whereIn('id', $modifierIds)
                        ->where('is_available', true)
                        ->get()
                    : collect();

                $modifierTotal = (float) $modifiers->sum('price');
                $unitSelling = (float) $price->selling_price + $modifierTotal;
                $lineSubtotal = $unitSelling * $qty;
                $lineDiscount = min((float) ($row['discount'] ?? 0), $lineSubtotal);
                $lineTotal = $lineSubtotal - $lineDiscount;

                $subtotal += $lineSubtotal;
                $itemDiscountTotal += $lineDiscount;

                $preparedItems[] = [
                    'variant' => $variant,
                    'stock' => $stock,
                    'qty' => $qty,
                    'cost_price' => (float) $price->cost_price,
                    'selling_price' => (float) $price->selling_price,
                    'subtotal' => $lineSubtotal,
                    'discount' => $lineDiscount,
                    'total' => $lineTotal,
                    'notes' => $row['notes'] ?? null,
                    'modifiers' => $modifiers,
                ];
            }

            $discount = $itemDiscountTotal + (float) ($data['discount_amount'] ?? 0);

            if ($customer?->group && (float) $customer->group->discount_percentage > 0) {
                $discount += ($subtotal - $discount) * ((float) $customer->group->discount_percentage / 100);
            }

            $voucher = null;
            if (! empty($data['voucher_code'])) {
                $voucher = Voucher::query()->where('code', strtoupper(trim($data['voucher_code'])))->first();
                if (! $voucher || ! $voucher->isUsable($subtotal)) {
                    throw new RuntimeException('Voucher tidak valid atau sudah kedaluwarsa.');
                }
                $discount += $voucher->discountFor($subtotal - $discount);
            }

            $discount = min(round($discount, 2), $subtotal);

            $pointsUsed = max(0, (int) ($data['points_used'] ?? 0));
            if ($pointsUsed > 0) {
                if (! $customer) {
                    throw new RuntimeException('Pelanggan wajib dipilih untuk menukarkan poin.');
                }
                if ($customer->loyalty_points < $pointsUsed) {
                    throw new RuntimeException('Poin pelanggan tidak mencukupi.');
                }
                $pointValue = (float) SystemSetting::get('loyalty_point_value', 100);
                $discount = min($subtotal, $discount + ($pointsUsed * $pointValue));
            }

            $taxPercentage = (float) SystemSetting::get('tax_percentage', 0);
            $taxable = max(0, $subtotal - $discount);
            $tax = round($taxable * ($taxPercentage / 100), 2);
            $grandTotal = round($taxable + $tax, 2);

            $paymentMethod = strtoupper($data['payment_method'] ?? 'CASH');
            if (! in_array($paymentMethod, ['CASH', 'QRIS', 'TRANSFER', 'DEBIT', 'CREDIT'], true)) {
                throw new InvalidArgumentException('Metode pembayaran tidak dikenal.');
            }

            $amountPaid = (float) ($data['amount_paid'] ?? 0);
            if ($paymentMethod === 'CASH' && $amountPaid < $grandTotal) {
                throw new RuntimeException('Nominal pembayaran kurang dari total tagihan.');
            }
            if ($paymentMethod !== 'CASH') {
                $amountPaid = max($amountPaid, $grandTotal);
            }
            $change = max(0, round($amountPaid - $grandTotal, 2));

            $transaction = Transaction::query()->create([
                'invoice_number' => InvoiceNumberHelper::generate('TRX', $outletId),
                'outlet_id' => $outletId,
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'voucher_id' => $voucher?->id,
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'status' => 'PAID',
                'notes' => $data['notes'] ?? null,
                'transaction_date' => now(),
            ]);

            foreach ($preparedItems as $prepared) {
                $item = $transaction->items()->create([
                    'product_variant_id' => $prepared['variant']->id,
                    'cost_price' => $prepared['cost_price'],
                    'selling_price' => $prepared['selling_price'],
                    'qty' => $prepared['qty'],
                    'subtotal' => $prepared['subtotal'],
                    'discount' => $prepared['discount'],
                    'total' => $prepared['total'],
                    'notes' => $prepared['notes'],
                ]);

                foreach ($prepared['modifiers'] as $modifier) {
                    $item->modifiers()->create([
                        'product_modifier_id' => $modifier->id,
                        'unit_price' => $modifier->price,
                    ]);
                }

                $prepared['stock']->decrement('quantity', $prepared['qty']);

                StockLog::query()->create([
                    'outlet_id' => $outletId,
                    'product_variant_id' => $prepared['variant']->id,
                    'user_id' => $user->id,
                    'type' => 'OUT',
                    'qty_change' => -$prepared['qty'],
                    'qty_balance' => $prepared['stock']->quantity - $prepared['qty'],
                    'reference_number' => $transaction->invoice_number,
                    'notes' => 'Penjualan kasir',
                    'created_at' => now(),
                ]);
            }

            $transaction->payments()->create([
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'change_returned' => $paymentMethod === 'CASH' ? $change : 0,
                'reference_note' => $data['reference_note'] ?? null,
            ]);

            if ($customer && $pointsUsed > 0) {
                $this->loyaltyPointService->redeem($customer, $pointsUsed, $transaction->invoice_number);
            }

            if ($customer) {
                $earnRate = (float) SystemSetting::get('loyalty_earn_per_amount', 10000);
                if ($earnRate > 0) {
                    $earned = (int) floor($grandTotal / $earnRate);
                    if ($earned > 0) {
                        $this->loyaltyPointService->earn($customer, $earned, $transaction->invoice_number);
                    }
                }
            }

            AuditLogger::log('transaction.created', Transaction::class, $transaction->id, null, [
                'invoice_number' => $transaction->invoice_number,
                'grand_total' => $grandTotal,
            ]);

            return $transaction->load(['items.modifiers', 'payments', 'customer', 'user', 'outlet']);
        });
    }
}
