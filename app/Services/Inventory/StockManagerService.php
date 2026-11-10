<?php

namespace App\Services\Inventory;

use App\Models\Stock;
use App\Models\StockLog;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockManagerService
{
    public function adjust(
        int $outletId,
        int $variantId,
        int $qtyChange,
        string $type = 'ADJUSTMENT',
        ?string $reference = null,
        ?string $notes = null,
        ?int $userId = null,
        ?int $minStock = null,
    ): Stock {
        if ($qtyChange === 0) {
            throw new RuntimeException('Perubahan stok tidak boleh nol.');
        }

        return DB::transaction(function () use ($outletId, $variantId, $qtyChange, $type, $reference, $notes, $userId, $minStock) {
            $stock = Stock::query()
                ->where('outlet_id', $outletId)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = Stock::query()->create([
                    'outlet_id' => $outletId,
                    'product_variant_id' => $variantId,
                    'quantity' => 0,
                    'min_stock' => $minStock ?? 5,
                ]);
            }

            $balance = $stock->quantity + $qtyChange;

            if ($balance < 0) {
                throw new RuntimeException('Stok tidak mencukupi untuk melakukan transaksi ini.');
            }

            $stock->update([
                'quantity' => $balance,
                'min_stock' => $minStock ?? $stock->min_stock,
            ]);

            StockLog::query()->create([
                'outlet_id' => $outletId,
                'product_variant_id' => $variantId,
                'user_id' => $userId,
                'type' => $type,
                'qty_change' => $qtyChange,
                'qty_balance' => $balance,
                'reference_number' => $reference,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            AuditLogger::log('stock.adjusted', Stock::class, $stock->id, null, [
                'variant_id' => $variantId,
                'change' => $qtyChange,
                'balance' => $balance,
                'type' => $type,
            ]);

            return $stock->refresh();
        });
    }

    public function transfer(int $transferId, int $fromOutletId, int $toOutletId, int $variantId, int $qty, ?int $userId): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Jumlah transfer harus lebih dari nol.');
        }

        DB::transaction(function () use ($transferId, $fromOutletId, $toOutletId, $variantId, $qty, $userId) {
            $reference = 'TRF-'.$transferId;
            $this->adjust($fromOutletId, $variantId, -$qty, 'TRANSFER', $reference, 'Transfer keluar', $userId);
            $this->adjust($toOutletId, $variantId, $qty, 'TRANSFER', $reference, 'Transfer masuk', $userId);
        });
    }
}
