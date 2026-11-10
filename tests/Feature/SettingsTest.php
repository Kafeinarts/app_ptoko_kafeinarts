<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_admin_can_create_user(): void
    {
        $this->actingAsSeeded();

        $this->post(route('settings.users.store'), [
            'name' => 'Kasir Cabang',
            'email' => 'kasir-cabang@kafeinarts.test',
            'phone' => '081200009999',
            'role_id' => Role::query()->where('name', 'Kasir')->value('id'),
            'password' => 'rahasia123',
            'is_active' => 1,
        ])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('users', ['email' => 'kasir-cabang@kafeinarts.test', 'is_active' => 1]);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $this->actingAsSeeded();

        $this->post(route('settings.users.store'), [
            'name' => 'Duplikat',
            'email' => 'admin@kafeinarts.test',
            'role_id' => Role::query()->where('name', 'Kasir')->value('id'),
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->actingAsSeeded();

        $this->delete(route('settings.users.destroy', $admin))
            ->assertSessionHas('swal_error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_update_general_settings(): void
    {
        $this->actingAsSeeded();

        $this->put(route('settings.general.update'), [
            'store_name' => 'Kafeinarts Pusat',
            'tax_percentage' => 11,
            'receipt_paper' => '80',
            'auto_print_receipt' => 1,
            'payment_cash' => 1,
            'payment_qris' => 1,
            'payment_transfer' => 0,
            'payment_debit' => 0,
            'loyalty_earn_per_amount' => 10000,
            'loyalty_point_value' => 150,
        ])->assertSessionHas('swal_success');

        $this->assertSame('Kafeinarts Pusat', SystemSetting::get('store_name'));
        $this->assertSame('80', SystemSetting::get('receipt_paper'));
        $this->assertSame('1', SystemSetting::get('auto_print_receipt'));
        $this->assertSame('0', SystemSetting::get('payment_transfer'));
        $this->assertSame('150', SystemSetting::get('loyalty_point_value'));
    }

    public function test_admin_can_sync_role_permissions(): void
    {
        $this->actingAsSeeded();

        $role = Role::query()->where('name', 'Kasir')->firstOrFail();
        $permissionIds = Permission::query()->whereIn('name', ['dashboard.view', 'pos.access'])->pluck('id')->all();

        $this->put(route('settings.roles.update', $role), [
            'permissions' => $permissionIds,
        ])->assertSessionHas('swal_success');

        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'pos.access'],
            $role->fresh()->permissions()->pluck('name')->all(),
        );
    }

    public function test_admin_can_create_and_deactivate_outlet(): void
    {
        $this->actingAsSeeded();

        $this->post(route('settings.outlets.store'), [
            'code' => 'KFT-SMG',
            'name' => 'Kafeinarts Semarang',
            'is_active' => 1,
        ])->assertSessionHas('swal_success');

        $outlet = Outlet::query()->where('code', 'KFT-SMG')->firstOrFail();
        $this->assertTrue($outlet->is_active);

        $this->delete(route('settings.outlets.destroy', $outlet))
            ->assertSessionHas('swal_success');

        $this->assertFalse($outlet->fresh()->is_active);
    }

    public function test_admin_can_open_settings_pages(): void
    {
        $this->actingAsSeeded();

        foreach (['settings.users', 'settings.roles', 'settings.outlets', 'settings.general', 'settings.logs'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_kasir_cannot_access_settings(): void
    {
        $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->get(route('settings.users'))->assertForbidden();
        $this->get(route('settings.general'))->assertForbidden();
    }
}
