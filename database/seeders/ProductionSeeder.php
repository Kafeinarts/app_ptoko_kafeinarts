<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Outlet;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder bersih untuk instalasi badan usaha / produksi.
 *
 * Yang dibuat HANYA data fondasi sistem:
 *   - RBAC (permissions, roles: Super Admin / Manajer / Kasir)
 *   - 1 outlet utama (bisa diubah lewat .env: OUTLET_NAME, OUTLET_CODE, ...)
 *   - 1 akun administrator (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD)
 *   - satuan umum, grup pelanggan, kategori kas, dan pengaturan dasar
 *
 * TIDAK membuat produk, transaksi, atau data contoh apa pun.
 *
 * Jalankan dengan: php artisan migrate:fresh --seed --class=ProductionSeeder
 * (SETELAH diedit sesuai .env produksi) — dan SEGERA ganti password admin.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        $outlet = Outlet::query()->firstOrCreate(
            ['code' => env('OUTLET_CODE', 'UT-01')],
            [
                'name' => env('OUTLET_NAME', 'Kantor Pusat'),
                'phone' => env('OUTLET_PHONE'),
                'email' => env('OUTLET_EMAIL'),
                'address' => env('OUTLET_ADDRESS'),
                'receipt_footer' => env('OUTLET_RECEIPT_FOOTER', 'Terima kasih atas kunjungan Anda'),
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'role_id' => \App\Models\Role::query()->where('name', 'Super Admin')->value('id'),
                'outlet_id' => $outlet->id,
                'phone' => env('ADMIN_PHONE'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'is_active' => true,
            ],
        );

        foreach ([['Cup', 'cup'], ['Pcs', 'pcs'], ['Porsi', 'porsi'], ['Pack', 'pack'], ['Kg', 'kg'], ['Gr', 'gr'], ['Botol', 'btl']] as [$name, $short]) {
            Unit::query()->firstOrCreate(['short_name' => $short], ['name' => $name]);
        }

        foreach ([['Reguler', 0], ['Member', 5], ['VIP', 10]] as [$name, $discount]) {
            CustomerGroup::query()->firstOrCreate(['name' => $name], ['discount_percentage' => $discount]);
        }

        foreach (['Operasional', 'Bahan Baku', 'Gaji', 'Listrik & Air', 'Perawatan', 'Perlengkapan', 'Lain-lain'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name]);
        }

        foreach (['Penjualan Lainnya', 'Jasa', 'Sewa Lahan', 'Lain-lain'] as $name) {
            IncomeCategory::query()->firstOrCreate(['name' => $name]);
        }

        $settings = [
            ['store_name', env('STORE_NAME', 'Kafeinarts'), 'general'],
            ['tax_percentage', env('TAX_PERCENTAGE', '11'), 'general'],
            ['receipt_paper', env('RECEIPT_PAPER', '58'), 'printer'],
            ['auto_print_receipt', env('AUTO_PRINT_RECEIPT', '0'), 'printer'],
            ['payment_cash', '1', 'payment'],
            ['payment_qris', '1', 'payment'],
            ['payment_transfer', '1', 'payment'],
            ['payment_debit', '1', 'payment'],
            ['loyalty_earn_per_amount', env('LOYALTY_EARN_PER_AMOUNT', '10000'), 'loyalty'],
            ['loyalty_point_value', env('LOYALTY_POINT_VALUE', '100'), 'loyalty'],
        ];
        foreach ($settings as [$key, $value, $group]) {
            SystemSetting::put($key, $value, $group);
        }
    }
}
