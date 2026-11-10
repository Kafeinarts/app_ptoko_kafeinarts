<?php

namespace Tests\Feature;

use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    private function openShift(User $user, float $startingCash = 500000): void
    {
        $this->actingAs($user)
            ->post(route('shifts.open'), ['starting_cash' => $startingCash])
            ->assertSessionHas('swal_success');
    }

    public function test_checkout_without_open_shift_is_rejected(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();
        $countBefore = Transaction::query()->count();

        $this->actingAs($kasir)
            ->postJson(route('pos.checkout'), [
                'items' => [['variant_id' => $variant->id, 'qty' => 1]],
                'payment_method' => 'CASH',
                'amount_paid' => 100000,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame($countBefore, Transaction::query()->count());
    }

    public function test_kasir_can_checkout_and_stock_is_decremented(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();
        $stockBefore = Stock::query()
            ->where('outlet_id', $kasir->outlet_id)
            ->where('product_variant_id', $variant->id)
            ->value('quantity');

        $this->openShift($kasir);

        $response = $this->postJson(route('pos.checkout'), [
            'items' => [['variant_id' => $variant->id, 'qty' => 2]],
            'payment_method' => 'CASH',
            'amount_paid' => 100000,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $transaction = Transaction::query()->latest('id')->firstOrFail();
        $this->assertSame(2, $transaction->items()->first()->qty);
        $this->assertDatabaseHas('stocks', [
            'outlet_id' => $kasir->outlet_id,
            'product_variant_id' => $variant->id,
            'quantity' => $stockBefore - 2,
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'product_variant_id' => $variant->id,
            'type' => 'OUT',
            'qty_change' => -2,
            'reference_number' => $transaction->invoice_number,
        ]);
        $this->assertDatabaseHas('transaction_payments', [
            'transaction_id' => $transaction->id,
            'payment_method' => 'CASH',
        ]);
    }

    public function test_checkout_change_is_calculated_from_amount_paid(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();
        $price = ProductPrice::query()->where('product_variant_id', $variant->id)
            ->where('outlet_id', $kasir->outlet_id)
            ->value('selling_price');

        $this->openShift($kasir);

        $this->postJson(route('pos.checkout'), [
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            'payment_method' => 'CASH',
            'amount_paid' => 100000,
        ])->assertOk();

        $transaction = Transaction::query()->latest('id')->firstOrFail();
        $expectedGrand = round($price * 1.11, 2);

        $this->assertEqualsWithDelta($expectedGrand, (float) $transaction->grand_total, 0.01);
        $this->assertEqualsWithDelta(round(100000 - $expectedGrand, 2), (float) $transaction->payments()->first()->change_returned, 0.01);
    }

    public function test_checkout_with_insufficient_stock_is_rejected(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();

        $countBefore = Transaction::query()->count();

        Stock::query()
            ->where('outlet_id', $kasir->outlet_id)
            ->where('product_variant_id', $variant->id)
            ->update(['quantity' => 0]);

        $this->openShift($kasir);

        $this->postJson(route('pos.checkout'), [
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            'payment_method' => 'CASH',
            'amount_paid' => 100000,
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame($countBefore, Transaction::query()->count());
    }

    public function test_checkout_with_voucher_applies_discount(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();

        $this->openShift($kasir);

        $this->postJson(route('pos.checkout'), [
            'items' => [['variant_id' => $variant->id, 'qty' => 4]],
            'payment_method' => 'QRIS',
            'amount_paid' => 0,
            'voucher_code' => 'KAFE10',
        ])->assertOk()->assertJsonPath('success', true);

        $transaction = Transaction::query()->latest('id')->firstOrFail();
        $this->assertGreaterThan(0, (float) $transaction->discount_amount);
        $this->assertNotNull($transaction->voucher_id);
    }

    public function test_checkout_with_invalid_voucher_is_rejected(): void
    {
        $kasir = $this->seededUser('kasir@kafeinarts.test');
        $variant = ProductVariant::query()->firstOrFail();

        $this->openShift($kasir);

        $this->postJson(route('pos.checkout'), [
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            'payment_method' => 'CASH',
            'amount_paid' => 100000,
            'voucher_code' => 'TIDAKADA1',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_void_requires_reason_and_restores_stock(): void
    {
        $admin = $this->actingAsSeeded();
        $kasir = User::query()->where('email', 'kasir@kafeinarts.test')->firstOrFail();
        $transaction = Transaction::query()->where('status', 'PAID')->firstOrFail();

        $stockBefore = Stock::query()
            ->where('outlet_id', $transaction->outlet_id)
            ->where('product_variant_id', $transaction->items()->first()->product_variant_id)
            ->value('quantity');

        // Reason terlalu pendek -> gagal validasi.
        $this->post(route('pos.history.void', $transaction), [
            'reason' => 'err',
            'approved_by' => $admin->id,
        ])->assertSessionHasErrors('reason');
        $this->post(route('pos.history.void', $transaction), [
            'reason' => 'Salah input item pelanggan',
            'approved_by' => $admin->id,
        ])->assertSessionHas('swal_success');

        $this->assertSame('VOID', $transaction->refresh()->status);
        $this->assertDatabaseHas('transaction_voids', [
            'transaction_id' => $transaction->id,
            'user_id' => $admin->id,
        ]);

        $item = $transaction->items()->first();
        $this->assertDatabaseHas('stocks', [
            'outlet_id' => $transaction->outlet_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $stockBefore + $item->qty,
        ]);
        $this->assertSame($kasir->id, $transaction->user_id);
    }

    public function test_return_flow_marks_transaction_as_partial_refund(): void
    {
        $admin = $this->actingAsSeeded();
        // Transaksi demo pertama berisi 1 item dengan qty 2, sehingga retur sebanyak 1
        // harus menghasilkan status PARTIAL_REFUND.
        $transaction = Transaction::query()->where('status', 'PAID')->orderBy('id')->firstOrFail();
        $item = $transaction->items()->firstOrFail();

        $this->post(route('pos.history.return.store', $transaction), [
            'reason' => 'Barang tidak sesuai',
            'items' => [
                ['item_id' => $item->id, 'qty' => 1],
            ],
        ])->assertRedirect(route('pos.history'));

        $this->assertSame('PARTIAL_REFUND', $transaction->refresh()->status);
        $this->assertDatabaseHas('transaction_return_items', [
            'transaction_item_id' => $item->id,
            'qty' => 1,
        ]);
    }
}
