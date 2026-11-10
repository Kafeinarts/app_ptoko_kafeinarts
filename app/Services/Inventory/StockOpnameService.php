<?php

namespace App\Services\Inventory;

use App\Helpers\InvoiceNumberHelper;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockOpnameService
{
    /**
     * @param  array<int, array{variant_id:int, physical_qty:int}>  $items
     */
    public function create(User $user, int $outletId, array $items, ?string $notes = null): StockOpname
    {
        if (empty($items)) {
            throw new RuntimeException('Tidak ada item opname yang dipilih.');
        }

        return DB::transaction(function () use ($user, $outletId, $items, $notes) {
            $opname = StockOpname::query()->create([
                'opname_number' => InvoiceNumberHelper::generate('OPN', $outletId),
                'outlet_id' => $outletId,
                'user_id' => $user->id,
                'opname_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            foreach ($items as $row) {
                $stock = Stock::query()
                    ->where('outlet_id', $outletId)
                    ->where('product_variant_id', $row['variant_id'])
                    ->lockForUpdate()
                    ->first();

                $systemQty = (int) ($stock->quantity ?? 0);
                $physicalQty = max(0, (int) $row['physical_qty']);
                $difference = $physicalQty - $systemQty;
                $cost = (float) (ProductPrice::query()
                    ->where('product_variant_id', $row['variant_id'])
                    ->where('outlet_id', $outletId)
                    ->value('cost_price') ?? 0);

                $opname->details()->create([
                    'product_variant_id' => $row['variant_id'],
                    'system_qty' => $systemQty,
                    'physical_qty' => $physicalQty,
                    'difference_qty' => $difference,
                    'difference_value' => $difference * $cost,
                ]);

                if (! $stock) {
                    $stock = Stock::query()->create([
                        'outlet_id' => $outletId,
                        'product_variant_id' => $row['variant_id'],
                        'quantity' => 0,
                        'min_stock' => 5,
                    ]);
                }

                $stock->update(['quantity' => $physicalQty]);

                \App\Models\StockLog::query()->create([
                    'outlet_id' => $outletId,
                    'product_variant_id' => $row['variant_id'],
                    'user_id' => $user->id,
                    'type' => 'ADJUSTMENT',
                    'qty_change' => $difference,
                    'qty_balance' => $physicalQty,
                    'reference_number' => $opname->opname_number,
                    'notes' => 'Stock opname',
                    'created_at' => now(),
                ]);
            }

            return $opname->load('details.variant.product');
        });
    }
}
