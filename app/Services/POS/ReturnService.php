<?php

namespace App\Services\POS;

use App\Helpers\InvoiceNumberHelper;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionReturn;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturnService
{
    public function void(Transaction $transaction, User $user, User $approver, string $reason): Transaction
    {
        if ($transaction->status === 'VOID') {
            throw new RuntimeException('Transaksi sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($transaction, $user, $approver, $reason) {
            foreach ($transaction->items as $item) {
                \App\Models\Stock::query()
                    ->where('outlet_id', $transaction->outlet_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->increment('quantity', $item->qty);

                $balance = (int) \App\Models\Stock::query()
                    ->where('outlet_id', $transaction->outlet_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->value('quantity');

                StockLog::query()->create([
                    'outlet_id' => $transaction->outlet_id,
                    'product_variant_id' => $item->product_variant_id,
                    'user_id' => $user->id,
                    'type' => 'VOID',
                    'qty_change' => $item->qty,
                    'qty_balance' => $balance,
                    'reference_number' => $transaction->invoice_number,
                    'notes' => 'Void transaksi: '.$reason,
                    'created_at' => now(),
                ]);
            }

            $transaction->update(['status' => 'VOID']);

            $transaction->void()->create([
                'user_id' => $user->id,
                'approved_by' => $approver->id,
                'reason' => $reason,
                'voided_at' => now(),
            ]);

            AuditLogger::log('transaction.voided', Transaction::class, $transaction->id, null, ['reason' => $reason]);

            return $transaction->refresh();
        });
    }

    /**
     * @param  array<int, array{item_id:int, qty:int}>  $items
     */
    public function returnItems(Transaction $transaction, User $user, array $items, ?string $reason): TransactionReturn
    {
        if ($transaction->status === 'VOID') {
            throw new RuntimeException('Transaksi void tidak dapat diretur.');
        }

        $items = array_values(array_filter($items, fn ($row) => (int) ($row['qty'] ?? 0) > 0));

        if (empty($items)) {
            throw new RuntimeException('Jumlah retur belum diisi.');
        }

        return DB::transaction(function () use ($transaction, $user, $items, $reason) {
            $return = TransactionReturn::query()->create([
                'return_number' => InvoiceNumberHelper::generate('RET', $transaction->outlet_id),
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'total_amount' => 0,
                'reason' => $reason,
                'return_date' => now(),
            ]);

            $total = 0;
            $allReturned = true;

            foreach ($transaction->items as $item) {
                $requested = collect($items)->firstWhere('item_id', $item->id)['qty'] ?? 0;
                $qty = min((int) $requested, $item->qty - $item->returned_qty);

                if ($qty > 0) {
                    $unit = (float) $item->selling_price + (float) $item->modifiers()->sum('unit_price');
                    $amount = $qty * $unit;
                    $total += $amount;

                    $return->items()->create([
                        'transaction_item_id' => $item->id,
                        'qty' => $qty,
                        'amount' => $amount,
                    ]);

                    $item->increment('returned_qty', $qty);

                    \App\Models\Stock::query()
                        ->where('outlet_id', $transaction->outlet_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->increment('quantity', $qty);

                    $balance = (int) \App\Models\Stock::query()
                        ->where('outlet_id', $transaction->outlet_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->value('quantity');

                    StockLog::query()->create([
                        'outlet_id' => $transaction->outlet_id,
                        'product_variant_id' => $item->product_variant_id,
                        'user_id' => $user->id,
                        'type' => 'RETURN',
                        'qty_change' => $qty,
                        'qty_balance' => $balance,
                        'reference_number' => $return->return_number,
                        'notes' => 'Retur penjualan',
                        'created_at' => now(),
                    ]);
                }

                if ($item->fresh()->returned_qty < $item->qty) {
                    $allReturned = false;
                }
            }

            if ($total <= 0) {
                throw new RuntimeException('Tidak ada item yang dapat diretur.');
            }

            $return->update(['total_amount' => $total]);
            $transaction->update(['status' => $allReturned ? 'REFUND' : 'PARTIAL_REFUND']);

            AuditLogger::log('transaction.returned', Transaction::class, $transaction->id, null, [
                'return_number' => $return->return_number,
                'total' => $total,
            ]);

            return $return->load('items.item.variant.product');
        });
    }
}
