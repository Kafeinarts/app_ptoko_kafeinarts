<?php

namespace App\Helpers;

use App\Models\Shift;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\Transaction;
use App\Models\TransactionReturn;
use Illuminate\Support\Str;

class InvoiceNumberHelper
{
    public const PREFIXES = [
        'TRX' => 'invoice_number',
        'PO' => 'po_number',
        'OPN' => 'opname_number',
        'TRF' => 'transfer_number',
        'RET' => 'return_number',
        'PUR' => 'return_number',
        'INV' => 'invoice_number',
    ];

    public static function generate(string $prefix, int $outletId = 1): string
    {
        $prefix = strtoupper($prefix);
        $date = now()->format('ymd');
        $pattern = $prefix.'-'.$outletId.'-'.$date.'-%';

        $last = match ($prefix) {
            'TRX', 'INV' => Transaction::query()->where('invoice_number', 'like', $pattern)->count(),
            'PO' => \App\Models\PurchaseOrder::query()->where('po_number', 'like', $pattern)->count(),
            'OPN' => StockOpname::query()->where('opname_number', 'like', $pattern)->count(),
            'TRF' => StockTransfer::query()->where('transfer_number', 'like', $pattern)->count(),
            'RET' => TransactionReturn::query()->where('return_number', 'like', $pattern)->count(),
            'PUR' => \App\Models\PurchaseReturn::query()->where('return_number', 'like', $pattern)->count(),
            default => 0,
        };

        do {
            $last++;
            $number = $prefix.'-'.$outletId.'-'.$date.'-'.str_pad((string) $last, 4, '0', STR_PAD_LEFT);
        } while (self::exists($prefix, $number));

        return $number;
    }

    protected static function exists(string $prefix, string $number): bool
    {
        return match ($prefix) {
            'TRX', 'INV' => Transaction::query()->where('invoice_number', $number)->exists(),
            'PO' => \App\Models\PurchaseOrder::query()->where('po_number', $number)->exists(),
            'OPN' => StockOpname::query()->where('opname_number', $number)->exists(),
            'TRF' => StockTransfer::query()->where('transfer_number', $number)->exists(),
            'RET' => TransactionReturn::query()->where('return_number', $number)->exists(),
            'PUR' => \App\Models\PurchaseReturn::query()->where('return_number', $number)->exists(),
            default => false,
        };
    }

    public static function shiftCode(Shift $shift): string
    {
        return 'SHIFT-'.strtoupper(Str::padLeft((string) $shift->id, 5, '0'));
    }
}
