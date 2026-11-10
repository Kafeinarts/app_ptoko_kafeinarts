<?php

namespace App\Services\POS;

use App\Helpers\InvoiceNumberHelper;
use App\Models\Shift;
use App\Models\ShiftCashLog;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShiftService
{
    public function open(User $user, int $outletId, float $startingCash): Shift
    {
        $existing = Shift::query()
            ->where('outlet_id', $outletId)
            ->where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->exists();

        if ($existing) {
            throw new RuntimeException('Anda masih memiliki shift yang terbuka.');
        }

        $shift = Shift::query()->create([
            'outlet_id' => $outletId,
            'user_id' => $user->id,
            'starting_cash' => $startingCash,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);

        AuditLogger::log('shift.opened', Shift::class, $shift->id, null, [
            'starting_cash' => $startingCash,
        ]);

        return $shift;
    }

    public function addCashLog(Shift $shift, string $type, float $amount, string $reason): ShiftCashLog
    {
        if ($shift->status !== 'OPEN') {
            throw new RuntimeException('Shift sudah ditutup.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Nominal harus lebih besar dari nol.');
        }

        return ShiftCashLog::query()->create([
            'shift_id' => $shift->id,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function expectedCash(Shift $shift): float
    {
        $cashSales = (float) Transaction::query()
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['PAID', 'PARTIAL_REFUND'])
            ->whereHas('payments', fn ($q) => $q->where('payment_method', 'CASH'))
            ->sum('grand_total');

        $cashIn = (float) $shift->cashLogs()->where('type', 'CASH_IN')->sum('amount');
        $cashOut = (float) $shift->cashLogs()->where('type', 'CASH_OUT')->sum('amount');

        return round((float) $shift->starting_cash + $cashSales + $cashIn - $cashOut, 2);
    }

    /**
     * @return array{expected:float, actual:float, difference:float, total_sales:float, total_transactions:int}
     */
    public function close(Shift $shift, float $actualCash, ?string $notes = null): array
    {
        if ($shift->status !== 'OPEN') {
            throw new RuntimeException('Shift sudah ditutup.');
        }

        return DB::transaction(function () use ($shift, $actualCash) {
            $expected = $this->expectedCash($shift);
            $difference = round($actualCash - $expected, 2);

            $shift->update([
                'ending_cash' => $expected,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'status' => 'CLOSED',
                'closed_at' => now(),
            ]);

            AuditLogger::log('shift.closed', Shift::class, $shift->id, null, [
                'expected' => $expected,
                'actual' => $actualCash,
                'difference' => $difference,
            ]);

            return $this->summary($shift);
        });
    }

    /**
     * @return array{expected:float, actual:float, difference:float, total_sales:float, total_transactions:int}
     */
    public function summary(Shift $shift): array
    {
        $transactionsQuery = Transaction::query()
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['PAID', 'PARTIAL_REFUND']);

        $totalSales = (float) (clone $transactionsQuery)->sum('grand_total');
        $totalTransactions = (clone $transactionsQuery)->count();

        return [
            'expected' => (float) ($shift->ending_cash ?? $this->expectedCash($shift)),
            'actual' => (float) ($shift->actual_cash ?? 0),
            'difference' => (float) ($shift->difference ?? 0),
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
        ];
    }

    public function label(Shift $shift): string
    {
        return InvoiceNumberHelper::shiftCode($shift);
    }
}
