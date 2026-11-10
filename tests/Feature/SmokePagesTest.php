<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Shift;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\Transaction;
use Tests\TestCase;

class SmokePagesTest extends TestCase
{
    public function test_admin_can_open_all_main_pages(): void
    {
        $admin = $this->actingAsSeeded();

        $product = Product::query()->with('variants')->firstOrFail();
        $transaction = Transaction::query()->firstOrFail();
        $shift = Shift::query()->firstOrFail();

        $po = PurchaseOrder::query()->create([
            'po_number' => 'PO-TEST-0001',
            'outlet_id' => $admin->outlet_id,
            'supplier_id' => Supplier::query()->firstOrFail()->id,
            'user_id' => $admin->id,
            'total_amount' => 150000,
            'status' => 'PENDING',
            'po_date' => now()->toDateString(),
        ]);
        $variant = ProductVariant::query()->firstOrFail();
        $po->items()->create([
            'product_variant_id' => $variant->id,
            'unit_cost' => 15000,
            'qty_ordered' => 10,
            'qty_received' => 0,
            'subtotal' => 150000,
        ]);

        $opname = StockOpname::query()->create([
            'opname_number' => 'OPN-TEST-0001',
            'outlet_id' => $admin->outlet_id,
            'user_id' => $admin->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Pengujian smoke',
        ]);
        $opname->details()->create([
            'product_variant_id' => $variant->id,
            'system_qty' => 100,
            'physical_qty' => 98,
            'difference_qty' => -2,
            'difference_value' => -30000,
        ]);

        $pages = [
            route('dashboard'),
            route('pos.index'),
            route('pos.history'),
            route('pos.history.show', $transaction),
            route('pos.history.return', $transaction),
            route('pos.receipt', $transaction),
            route('shifts.index'),
            route('shifts.receipt', $shift),
            route('master.products.index'),
            route('master.products.create'),
            route('master.products.edit', $product),
            route('master.products.price-draft'),
            route('master.categories.index'),
            route('master.brands.index'),
            route('master.units.index'),
            route('master.customers.index'),
            route('master.suppliers.index'),
            route('master.modifiers.index'),
            route('inventory.stocks'),
            route('inventory.stocks.create'),
            route('inventory.opnames.index'),
            route('inventory.opnames.create'),
            route('inventory.opnames.show', $opname),
            route('inventory.transfers.index'),
            route('inventory.transfers.create'),
            route('purchasing.index'),
            route('purchasing.create'),
            route('purchasing.show', $po),
            route('promotions.index'),
            route('finance.expenses'),
            route('finance.incomes'),
            route('reports.sales'),
            route('reports.finance'),
            route('reports.sales.pdf'),
            route('reports.finance.pdf'),
            route('reports.sales.excel'),
            route('master.products.export'),
            route('master.products.template'),
            route('settings.users'),
            route('settings.roles'),
            route('settings.outlets'),
            route('settings.general'),
            route('settings.logs'),
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_kasir_cannot_open_admin_only_pages(): void
    {
        $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->get(route('settings.users'))->assertForbidden();
        $this->get(route('settings.roles'))->assertForbidden();
        $this->get(route('settings.logs'))->assertForbidden();
        $this->get(route('reports.sales'))->assertForbidden();
        $this->get(route('purchasing.index'))->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_open_pos(): void
    {
        $this->get(route('pos.index'))->assertRedirect(route('login'));
    }
}
