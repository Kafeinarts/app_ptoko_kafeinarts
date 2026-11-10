<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Support\OutletContext;
use Tests\TestCase;

class ShiftFlowTest extends TestCase
{
    public function test_kasir_can_open_and_close_shift(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->post(route('shifts.open'), ['starting_cash' => 250000])
            ->assertSessionHas('swal_success');

        $shift = Shift::query()
            ->where('user_id', $kasir->id)
            ->where('status', 'OPEN')
            ->firstOrFail();

        $this->assertEquals(250000, (float) $shift->starting_cash);

        $this->post(route('shifts.close'), [
            'shift_id' => $shift->id,
            'actual_cash' => 250000,
        ])->assertRedirect(route('shifts.receipt', $shift));

        $this->assertSame('CLOSED', $shift->fresh()->status);
        $this->assertEquals(0.0, (float) $shift->fresh()->difference);
    }

    public function test_kasir_cannot_open_second_shift_while_one_is_open(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->post(route('shifts.open'), ['starting_cash' => 100000])
            ->assertSessionHas('swal_success');

        $this->post(route('shifts.open'), ['starting_cash' => 100000])
            ->assertSessionHas('swal_error');

        $this->assertSame(1, Shift::query()->where('user_id', $kasir->id)->where('status', 'OPEN')->count());
    }

    public function test_kasir_can_log_cash_in_and_cash_out(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->post(route('shifts.open'), ['starting_cash' => 100000]);

        $shift = Shift::query()->where('user_id', $kasir->id)->where('status', 'OPEN')->firstOrFail();

        $this->post(route('shifts.cash-log'), [
            'shift_id' => $shift->id,
            'type' => 'CASH_OUT',
            'amount' => 50000,
            'reason' => 'Beli es batu',
        ])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('shift_cash_logs', [
            'shift_id' => $shift->id,
            'type' => 'CASH_OUT',
            'amount' => 50000,
            'reason' => 'Beli es batu',
        ]);

        $this->post(route('shifts.cash-log'), [
            'shift_id' => $shift->id,
            'type' => 'CASH_IN',
            'amount' => 20000,
            'reason' => 'Setor modal tambahan',
        ])->assertSessionHas('swal_success');

        $expected = 100000 + 20000 - 50000;
        $this->assertEquals($expected, app(\App\Services\POS\ShiftService::class)->expectedCash($shift->fresh()));
    }

    public function test_closed_shift_cannot_receive_cash_log(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->post(route('shifts.open'), ['starting_cash' => 100000]);
        $shift = Shift::query()->where('user_id', $kasir->id)->where('status', 'OPEN')->firstOrFail();
        $this->post(route('shifts.close'), ['shift_id' => $shift->id, 'actual_cash' => 100000]);

        $this->post(route('shifts.cash-log'), [
            'shift_id' => $shift->id,
            'type' => 'CASH_OUT',
            'amount' => 10000,
            'reason' => 'Tutup kas',
        ])->assertSessionHas('swal_error');
    }

    public function test_shift_receipt_is_pdf(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');
        $shift = Shift::query()->firstOrFail();

        $response = $this->get(route('shifts.receipt', $shift));
        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_outlet_context_defaults_to_user_outlet(): void
    {
        $kasir = $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->assertSame($kasir->outlet_id, OutletContext::id());
    }
}
