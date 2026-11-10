<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder RBAC generik (daftar permission + role awal).
 * Dipakai oleh demo seeder dan production seeder.
 */
class RbacSeeder extends Seeder
{
    public const PERMISSION_MAP = [
        'Dashboard' => ['dashboard.view'],
        'POS' => ['pos.access', 'pos.checkout', 'pos.history', 'pos.void', 'pos.return'],
        'Shift' => ['shift.manage'],
        'Master Data' => ['master.products', 'master.categories', 'master.brands', 'master.units', 'master.customers', 'master.suppliers', 'master.modifiers'],
        'Inventaris' => ['inventory.view', 'inventory.adjust', 'inventory.opname', 'inventory.transfer'],
        'Pembelian' => ['purchasing.view', 'purchasing.manage'],
        'Promosi' => ['promotion.manage'],
        'Keuangan' => ['finance.view', 'finance.manage'],
        'Laporan' => ['report.sales', 'report.finance', 'report.export'],
        'Pengaturan' => ['settings.users', 'settings.roles', 'settings.outlets', 'settings.general', 'settings.logs'],
    ];

    public function run(): void
    {
        $permissions = [];
        foreach (self::PERMISSION_MAP as $group => $names) {
            foreach ($names as $name) {
                $permissions[$name] = Permission::query()->firstOrCreate(
                    ['name' => $name],
                    ['group_name' => $group, 'description' => ucfirst(str_replace(['.', '_'], [' ', ' '], $name))],
                );
            }
        }

        $superAdmin = Role::query()->firstOrCreate(
            ['name' => 'Super Admin'],
            ['description' => 'Akses penuh ke seluruh fitur sistem'],
        );
        $superAdmin->permissions()->sync(collect($permissions)->pluck('id'));

        $manager = Role::query()->firstOrCreate(
            ['name' => 'Manajer'],
            ['description' => 'Mengelola operasional harian, laporan, dan outlet'],
        );
        $managerPermissions = collect($permissions)->keys()->reject(fn ($name) => in_array($name, ['settings.roles', 'settings.users'], true))->values();
        $manager->permissions()->sync(collect($permissions)->only($managerPermissions)->pluck('id'));

        $cashier = Role::query()->firstOrCreate(
            ['name' => 'Kasir'],
            ['description' => 'Akses kasir, transaksi, dan shift'],
        );
        $cashierPermissions = ['dashboard.view', 'pos.access', 'pos.checkout', 'pos.history', 'shift.manage', 'master.customers'];
        $cashier->permissions()->sync(collect($permissions)->only($cashierPermissions)->pluck('id'));
    }
}
