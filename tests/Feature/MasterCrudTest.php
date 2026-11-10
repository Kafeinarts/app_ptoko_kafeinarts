<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Unit;
use Tests\TestCase;

class MasterCrudTest extends TestCase
{
    public function test_admin_can_create_update_and_delete_category(): void
    {
        $this->actingAsSeeded();

        $this->post(route('master.categories.store'), [
            'name' => 'Minuman Signature',
            'is_active' => 1,
        ])->assertSessionHas('swal_success');

        $category = Category::query()->where('name', 'Minuman Signature')->firstOrFail();
        $this->assertTrue($category->is_active);

        $this->put(route('master.categories.update', $category->id), [
            'name' => 'Minuman Signature Baru',
            'is_active' => 1,
        ])->assertSessionHas('swal_success');
        $this->assertSame('Minuman Signature Baru', $category->fresh()->name);

        $this->delete(route('master.categories.destroy', $category->id))
            ->assertSessionHas('swal_success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAsSeeded();

        $this->post(route('master.categories.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_create_unit(): void
    {
        $this->actingAsSeeded();

        $this->post(route('master.units.store'), [
            'name' => 'Box',
            'short_name' => 'box',
        ])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('units', ['name' => 'Box', 'short_name' => 'box']);
        $this->assertSame(Unit::query()->where('name', 'Box')->first()->short_name, 'box');
    }

    public function test_kasir_cannot_manage_master_data(): void
    {
        $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->post(route('master.categories.store'), ['name' => 'X'])->assertForbidden();
        $this->get(route('master.categories.index'))->assertForbidden();
        $this->get(route('master.products.index'))->assertForbidden();
    }
}
