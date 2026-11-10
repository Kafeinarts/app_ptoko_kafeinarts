<?php

namespace App\Services\Purchasing;

use App\Helpers\InvoiceNumberHelper;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    /**
     * @param  array<int, array{variant_id:int, unit_cost:float, qty:int}>  $items
     */
    public function create(User $user, int $outletId, int $supplierId, array $items, ?string $notes = null): PurchaseOrder
    {
        $items = array_values(array_filter($items, fn ($row) => ! empty($row['variant_id']) && (int) ($row['qty'] ?? 0) > 0));

        if (empty($items)) {
            throw new RuntimeException('Minimal satu item pembelian harus diisi.');
        }

        return DB::transaction(function () use ($user, $outletId, $supplierId, $items, $notes) {
            $po = PurchaseOrder::query()->create([
                'po_number' => InvoiceNumberHelper::generate('PO', $outletId),
                'outlet_id' => $outletId,
                'supplier_id' => $supplierId,
                'user_id' => $user->id,
                'total_amount' => 0,
                'status' => 'PENDING',
                'po_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            $total = 0;
            foreach ($items as $row) {
                ProductVariant::query()->findOrFail($row['variant_id']);
                $subtotal = (float) $row['unit_cost'] * (int) $row['qty'];
                $total += $subtotal;

                $po->items()->create([
                    'product_variant_id' => $row['variant_id'],
                    'unit_cost' => $row['unit_cost'],
                    'qty_ordered' => (int) $row['qty'],
                    'qty_received' => 0,
                    'subtotal' => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $total]);
            AuditLogger::log('purchase_order.created', PurchaseOrder::class, $po->id, null, ['total' => $total]);

            return $po->load('items.variant.product', 'supplier');
        });
    }

    public function receive(PurchaseOrder $po, User $user, \App\Services\Inventory\StockManagerService $stockManager): PurchaseOrder
    {
        if ($po->status !== 'PENDING') {
            throw new RuntimeException('Hanya PO berstatus PENDING yang dapat diterima.');
        }

        return DB::transaction(function () use ($po, $user, $stockManager) {
            foreach ($po->items as $item) {
                $qty = $item->qty_ordered - $item->qty_received;

                if ($qty <= 0) {
                    continue;
                }

                $stockManager->adjust(
                    $po->outlet_id,
                    $item->product_variant_id,
                    $qty,
                    'IN',
                    $po->po_number,
                    'Penerimaan barang dari supplier',
                    $user->id,
                );

                $item->increment('qty_received', $qty);
            }

            $po->update(['status' => 'RECEIVED']);
            AuditLogger::log('purchase_order.received', PurchaseOrder::class, $po->id);

            return $po->refresh()->load('items.variant.product');
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status === 'RECEIVED') {
            throw new RuntimeException('PO yang sudah diterima tidak dapat dibatalkan.');
        }

        $po->update(['status' => 'CANCELLED']);

        return $po->refresh();
    }

    /**
     * @param  array<int, array{item_id:int, qty:int}>  $items
     */
    public function createReturn(PurchaseOrder $po, User $user, array $items, ?string $reason, \App\Services\Inventory\StockManagerService $stockManager): PurchaseReturn
    {
        if ($po->status !== 'RECEIVED') {
            throw new RuntimeException('Retur hanya dapat dilakukan untuk PO yang sudah diterima.');
        }

        $items = array_values(array_filter($items, fn ($row) => (int) ($row['qty'] ?? 0) > 0));

        if (empty($items)) {
            throw new RuntimeException('Jumlah retur belum diisi.');
        }

        return DB::transaction(function () use ($po, $user, $items, $reason, $stockManager) {
            $purchaseReturn = PurchaseReturn::query()->create([
                'return_number' => InvoiceNumberHelper::generate('PUR', $po->outlet_id),
                'purchase_order_id' => $po->id,
                'user_id' => $user->id,
                'total_amount' => 0,
                'reason' => $reason,
                'return_date' => now()->toDateString(),
            ]);

            $total = 0;
            foreach ($items as $row) {
                $item = PurchaseOrderItem::query()->findOrFail($row['item_id']);
                $qty = min((int) $row['qty'], $item->qty_received);

                if ($qty <= 0) {
                    continue;
                }

                $subtotal = $qty * (float) $item->unit_cost;
                $total += $subtotal;

                $purchaseReturn->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'qty' => $qty,
                    'unit_cost' => $item->unit_cost,
                    'subtotal' => $subtotal,
                ]);

                $stockManager->adjust(
                    $po->outlet_id,
                    $item->product_variant_id,
                    -$qty,
                    'RETURN',
                    $purchaseReturn->return_number,
                    'Retur pembelian ke supplier',
                    $user->id,
                );
            }

            $purchaseReturn->update(['total_amount' => $total]);

            return $purchaseReturn->load('items');
        });
    }
}
