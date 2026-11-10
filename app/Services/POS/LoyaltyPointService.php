<?php

namespace App\Services\POS;

use App\Models\Customer;
use App\Models\CustomerPointLog;
use InvalidArgumentException;

class LoyaltyPointService
{
    public function earn(Customer $customer, int $points, ?string $reference = null): int
    {
        if ($points <= 0) {
            return $customer->loyalty_points;
        }

        $customer->increment('loyalty_points', $points);

        CustomerPointLog::query()->create([
            'customer_id' => $customer->id,
            'type' => 'EARNED',
            'points' => $points,
            'reference_number' => $reference,
            'created_at' => now(),
        ]);

        return $customer->refresh()->loyalty_points;
    }

    public function redeem(Customer $customer, int $points, ?string $reference = null): int
    {
        if ($points <= 0) {
            return $customer->loyalty_points;
        }

        if ($customer->loyalty_points < $points) {
            throw new InvalidArgumentException('Poin pelanggan tidak mencukupi.');
        }

        $customer->decrement('loyalty_points', $points);

        CustomerPointLog::query()->create([
            'customer_id' => $customer->id,
            'type' => 'REDEEMED',
            'points' => -$points,
            'reference_number' => $reference,
            'created_at' => now(),
        ]);

        return $customer->refresh()->loyalty_points;
    }
}
