<?php

namespace App\Services\Inventory;

use App\Helpers\InvoiceNumberHelper;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockTransferService
{
    /**
     * @param  array<int, array{variant_id:int, qty:int}>  $items
     */
    public function create(User $user, int $fromOutletId, int $toOutletId, array $items, ?string $notes = null): StockTransfer
    {
        if ($fromOutletId === $toOutletId) {
            throw new RuntimeException('Outlet asal dan tujuan tidak boleh sama.');
        }

        if (empty($items)) {
            throw new RuntimeException('Tidak ada item transfer.');
        }

        return DB::transaction(function () use ($user, $fromOutletId, $toOutletId, $items, $notes) {
            $transfer = StockTransfer::query()->create([
                'transfer_number' => InvoiceNumberHelper::generate('TRF', $fromOutletId),
                'from_outlet_id' => $fromOutletId,
                'to_outlet_id' => $toOutletId,
                'user_id' => $user->id,
                'status' => 'SENT',
                'transfer_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            foreach ($items as $row) {
                $transfer->details()->create([
                    'product_variant_id' => $row['variant_id'],
                    'qty' => (int) $row['qty'],
                ]);
            }

            return $transfer->load('details.variant.product', 'fromOutlet', 'toOutlet');
        });
    }

    public function receive(StockTransfer $transfer, StockManagerService $stockManager, ?int $userId): StockTransfer
    {
        if ($transfer->status === 'RECEIVED') {
            throw new RuntimeException('Transfer sudah diterima.');
        }

        return DB::transaction(function () use ($transfer, $stockManager, $userId) {
            foreach ($transfer->details as $detail) {
                $stockManager->adjust(
                    $transfer->from_outlet_id,
                    $detail->product_variant_id,
                    -$detail->qty,
                    'TRANSFER',
                    $transfer->transfer_number,
                    'Transfer keluar',
                    $userId,
                );
                $stockManager->adjust(
                    $transfer->to_outlet_id,
                    $detail->product_variant_id,
                    $detail->qty,
                    'TRANSFER',
                    $transfer->transfer_number,
                    'Transfer masuk',
                    $userId,
                );
            }

            $transfer->update(['status' => 'RECEIVED']);

            return $transfer->refresh();
        });
    }
}
