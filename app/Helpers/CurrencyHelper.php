<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function format(float|int|string|null $amount, bool $withPrefix = true): string
    {
        $formatted = number_format((float) $amount, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }

    public static function parse(string|float|int|null $amount): float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        return (float) preg_replace('/[^0-9\-]/', '', (string) $amount);
    }

    public static function short(float|int|null $amount): string
    {
        $value = (float) $amount;

        return match (true) {
            abs($value) >= 1_000_000_000 => 'Rp '.number_format($value / 1_000_000_000, 1, ',', '.').' M',
            abs($value) >= 1_000_000 => 'Rp '.number_format($value / 1_000_000, 1, ',', '.').' Jt',
            abs($value) >= 1_000 => 'Rp '.number_format($value / 1_000, 0, ',', '.').' Rb',
            default => self::format($value),
        };
    }
}
