<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\ProductModifierGroup;
use Tests\TestCase;

class InsertPagesTest extends TestCase
{
    public function test_admin_can_open_all_master_insert_pages(): void
    {
        $this->actingAsSeeded();

        foreach (['customers', 'categories', 'brands', 'units', 'modifiers', 'suppliers'] as $module) {
            $this->get(route("master.{$module}.insert"))->assertOk();
        }
    }

    public function test_insert_form_posts_to_store_and_redirects_to_index(): void
    {
        $this->actingAsSeeded();

        $this->post(route('master.units.store'), [
            'name' => 'Insert Test Unit',
            'short_name' => 'ins',
        ])->assertRedirect(route('master.units.index'))
            ->assertSessionHas('swal_success');

        $this->assertDatabaseHas('units', ['name' => 'Insert Test Unit', 'short_name' => 'ins']);
    }

    public function test_insert_validation_error_returns_to_page_with_errors(): void
    {
        $this->actingAsSeeded();

        $this->post(route('master.categories.store'), [])->assertSessionHasErrors('name');
    }

    public function test_modifier_insert_page_creates_group_with_items(): void
    {
        $this->actingAsSeeded();
        $product = Product::query()->firstOrFail();

        $this->post(route('master.modifiers.groups.store'), [
            'name' => 'Grup Insert Demo',
            'min_selection' => 1,
            'max_selection' => 2,
            'is_required' => 1,
            'product_ids' => [$product->id],
            'items' => [
                ['name' => 'Opsi Satu', 'price' => 2500, 'is_available' => 1],
                ['name' => '', 'price' => 999, 'is_available' => 1], // baris kosong diabaikan
                ['name' => 'Opsi Dua', 'price' => 0, 'is_available' => 1],
            ],
        ])->assertRedirect(route('master.modifiers.index'))
            ->assertSessionHas('swal_success');

        $group = ProductModifierGroup::query()->where('name', 'Grup Insert Demo')->firstOrFail();
        $this->assertTrue($group->is_required);
        $this->assertEquals(1, $group->min_selection);
        $this->assertEquals(2, $group->max_selection);
        $this->assertTrue($group->products()->whereKey($product->id)->exists());

        $modifiers = ProductModifier::query()->where('modifier_group_id', $group->id)->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Opsi Satu', 'Opsi Dua'], $modifiers);
    }

    public function test_kasir_cannot_open_master_insert_pages(): void
    {
        $this->actingAsSeeded('kasir@kafeinarts.test');

        foreach (['master.categories.insert', 'master.units.insert', 'master.modifiers.insert'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }
}
