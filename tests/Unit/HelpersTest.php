<?php

namespace Tests\Unit;

use App\Helpers\CurrencyHelper;
use App\Models\Voucher;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_currency_format(): void
    {
        $this->assertSame('Rp 125.000', CurrencyHelper::format(125000));
        $this->assertSame('Rp 0', CurrencyHelper::format(null));
        $this->assertSame('125.000', CurrencyHelper::format(125000, false));
    }

    public function test_currency_parse(): void
    {
        $this->assertSame(125000.0, CurrencyHelper::parse('Rp 125.000'));
        $this->assertSame(500000.0, CurrencyHelper::parse('500000'));
    }

    public function test_currency_short(): void
    {
        $this->assertSame('Rp 2,5 Jt', CurrencyHelper::short(2500000));
        $this->assertSame('Rp 750 Rb', CurrencyHelper::short(750000));
    }

    public function test_voucher_percentage_discount(): void
    {
        $voucher = new Voucher([
            'code' => 'TEST10',
            'name' => 'Test',
            'type' => 'PERCENTAGE',
            'value' => 10,
            'min_purchase' => 50000,
            'max_discount' => 25000,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->assertSame(10000.0, $voucher->discountFor(100000));
        // Cap max_discount.
        $this->assertSame(25000.0, $voucher->discountFor(500000));
    }

    public function test_voucher_fixed_amount_does_not_exceed_subtotal(): void
    {
        $voucher = new Voucher([
            'code' => 'HEMAT',
            'name' => 'Test',
            'type' => 'FIXED_AMOUNT',
            'value' => 50000,
            'min_purchase' => 0,
            'max_discount' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->assertSame(50000.0, $voucher->discountFor(100000));
        $this->assertSame(30000.0, $voucher->discountFor(30000));
    }
}
