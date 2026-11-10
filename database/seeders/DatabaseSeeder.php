<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\ProductModifierGroup;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // Outlets
        // ------------------------------------------------------------------
        $outletHQ = Outlet::query()->create([
            'code' => 'KFT-HQ',
            'name' => 'Kafeinarts Gatot Subroto',
            'phone' => '021-5550101',
            'email' => 'hq@kafeinarts.test',
            'address' => 'Jl. Gatot Subroto No. 12, Jakarta Selatan',
            'receipt_footer' => 'Terima kasih sudah singgah di Kafeinarts!',
            'is_active' => true,
        ]);

        $outletBranch = Outlet::query()->create([
            'code' => 'KFT-BDG',
            'name' => 'Kafeinarts Dago Bandung',
            'phone' => '022-5550102',
            'email' => 'dago@kafeinarts.test',
            'address' => 'Jl. Ir. H. Juanda No. 88, Dago, Bandung',
            'receipt_footer' => 'See you again!',
            'is_active' => true,
        ]);

        // ------------------------------------------------------------------
        // Permissions & Roles
        // ------------------------------------------------------------------
        $permissionMap = [
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

        $permissions = [];
        foreach ($permissionMap as $group => $names) {
            foreach ($names as $name) {
                $permissions[$name] = Permission::query()->create([
                    'name' => $name,
                    'group_name' => $group,
                    'description' => ucfirst(str_replace(['.', '_'], [' ', ' '], $name)),
                ]);
            }
        }

        $superAdmin = Role::query()->create([
            'name' => 'Super Admin',
            'description' => 'Akses penuh ke seluruh fitur sistem',
        ]);
        $superAdmin->permissions()->sync(collect($permissions)->pluck('id'));

        $manager = Role::query()->create([
            'name' => 'Manajer',
            'description' => 'Mengelola operasional harian, laporan, dan outlet',
        ]);
        $managerPermissions = collect($permissions)->keys()->reject(fn ($name) => in_array($name, ['settings.roles', 'settings.users'], true))->values();
        $manager->permissions()->sync(collect($permissions)->only($managerPermissions)->pluck('id'));

        $cashier = Role::query()->create([
            'name' => 'Kasir',
            'description' => 'Akses kasir, transaksi, dan shift',
        ]);
        $cashierPermissions = ['dashboard.view', 'pos.access', 'pos.checkout', 'pos.history', 'shift.manage', 'master.customers'];
        $cashier->permissions()->sync(collect($permissions)->only($cashierPermissions)->pluck('id'));

        // ------------------------------------------------------------------
        // Users
        // ------------------------------------------------------------------
        $admin = User::query()->create([
            'outlet_id' => $outletHQ->id,
            'role_id' => $superAdmin->id,
            'name' => 'Administrator Kafeinarts',
            'email' => 'admin@kafeinarts.test',
            'phone' => '081200000100',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $managerUser = User::query()->create([
            'outlet_id' => $outletHQ->id,
            'role_id' => $manager->id,
            'name' => 'Manajer Operasional',
            'email' => 'manager@kafeinarts.test',
            'phone' => '081200000101',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $kasir = User::query()->create([
            'outlet_id' => $outletHQ->id,
            'role_id' => $cashier->id,
            'name' => 'Kasir Kafeinarts',
            'email' => 'kasir@kafeinarts.test',
            'phone' => '081200000102',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $kasirBranch = User::query()->create([
            'outlet_id' => $outletBranch->id,
            'role_id' => $cashier->id,
            'name' => 'Kasir Dago',
            'email' => 'kasir.dago@kafeinarts.test',
            'phone' => '081200000103',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // ------------------------------------------------------------------
        // Categories, Brands, Units
        // ------------------------------------------------------------------
        $categoryNames = ['Kopi', 'Non Kopi', 'Tea & Latte', 'Makanan', 'Snack', 'Merchandise'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $categories[$name] = Category::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ]);
        }

        $brands = [];
        foreach (['Kafeinarts', 'Local Beans', 'Dairy Fresh'] as $name) {
            $brands[$name] = Brand::query()->create(['name' => $name]);
        }

        $units = [];
        foreach ([['Cup', 'cup'], ['Pcs', 'pcs'], ['Porsi', 'porsi'], ['Pack', 'pack'], ['Kg', 'kg'], ['Gr', 'gr'], ['Botol', 'btl']] as [$name, $short]) {
            $units[$name] = Unit::query()->create(['name' => $name, 'short_name' => $short]);
        }

        // ------------------------------------------------------------------
        // Products, Variants, Prices (both outlets)
        // ------------------------------------------------------------------
        $productDefinitions = [
            ['Kopi', 'Cup', 'Kafeinarts', 'Espresso', [['Single', 6000, 18000], ['Double', 10000, 24000]]],
            ['Kopi', 'Cup', 'Kafeinarts', 'Americano', [['Regular', 7000, 20000], ['Large', 11000, 26000]]],
            ['Kopi', 'Cup', 'Kafeinarts', 'Cafe Latte', [['Regular', 9000, 25000], ['Large', 14000, 32000]]],
            ['Kopi', 'Cup', 'Kafeinarts', 'Cappuccino', [['Regular', 9000, 24000], ['Large', 14000, 31000]]],
            ['Kopi', 'Cup', 'Local Beans', 'Caramel Macchiato', [['Regular', 10000, 30000], ['Large', 15000, 38000]]],
            ['Kopi', 'Cup', 'Kafeinarts', 'Mocha', [['Regular', 11000, 28000], ['Large', 16000, 35000]]],
            ['Kopi', 'Cup', 'Local Beans', 'Kopi Gula Aren', [['Regular', 9000, 23000], ['Large', 13000, 29000]]],
            ['Non Kopi', 'Cup', 'Kafeinarts', 'Matcha Latte', [['Regular', 12000, 27000], ['Large', 17000, 34000]]],
            ['Non Kopi', 'Cup', 'Dairy Fresh', 'Chocolate Latte', [['Regular', 11000, 26000], ['Large', 16000, 33000]]],
            ['Non Kopi', 'Cup', 'Dairy Fresh', 'Vanilla Latte', [['Regular', 10000, 25000], ['Large', 15000, 32000]]],
            ['Tea & Latte', 'Cup', 'Kafeinarts', 'Lemon Iced Tea', [['Regular', 5000, 16000], ['Large', 8000, 21000]]],
            ['Tea & Latte', 'Cup', 'Kafeinarts', 'Thai Tea', [['Regular', 7000, 20000], ['Large', 10000, 25000]]],
            ['Makanan', 'Porsi', 'Kafeinarts', 'Spaghetti Aglio Olio', [['Regular', 15000, 42000]]],
            ['Makanan', 'Pcs', 'Kafeinarts', 'Croissant', [['Regular', 8000, 22000]]],
            ['Makanan', 'Pcs', 'Kafeinarts', 'Chicken Sandwich', [['Regular', 16000, 35000]]],
            ['Snack', 'Porsi', 'Kafeinarts', 'French Fries', [['Regular', 9000, 20000]]],
            ['Snack', 'Porsi', 'Kafeinarts', 'Cheese Stick', [['Regular', 8000, 18000]]],
            ['Merchandise', 'Pcs', 'Kafeinarts', 'Tumbler Kafeinarts', [['Regular', 45000, 125000]]],
            ['Merchandise', 'Pack', 'Local Beans', 'Kafeinarts Beans 250g', [['Regular', 40000, 90000]]],
        ];

        $drinkCategoryNames = ['Kopi', 'Non Kopi', 'Tea & Latte'];
        $products = [];
        $productVariants = [];
        foreach ($productDefinitions as $index => [$categoryName, $unitName, $brandName, $name, $variants]) {
            $sku = 'KFN-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $product = Product::query()->create([
                'category_id' => $categories[$categoryName]->id,
                'brand_id' => $brands[$brandName]->id,
                'unit_id' => $units[$unitName]->id,
                'sku' => $sku,
                'barcode' => '8990000' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'name' => $name,
                'description' => 'Produk unggulan Kafeinarts — ' . $name,
                'is_active' => true,
            ]);

            foreach ($variants as $variantIndex => [$variantName, $cost, $price]) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'variant_name' => $variantName,
                    'sku_variant' => $sku . '-' . strtoupper(Str::substr(Str::slug($variantName, ''), 0, 3)) . ($variantIndex === 0 ? '' : '-' . $variantIndex),
                ]);

                // Branch outlet price is 5% higher to demo outlet-specific pricing.
                foreach ([[$outletHQ, 1.0], [$outletBranch, 1.05]] as [$outlet, $multiplier]) {
                    ProductPrice::query()->create([
                        'product_variant_id' => $variant->id,
                        'outlet_id' => $outlet->id,
                        'cost_price' => $cost,
                        'selling_price' => round($price * $multiplier, 2),
                    ]);
                }

                $productVariants[$product->id][] = $variant;
            }

            $products[] = $product;
        }

        // ------------------------------------------------------------------
        // Modifier Groups & Modifiers
        // ------------------------------------------------------------------
        $addonGroup = ProductModifierGroup::query()->create([
            'name' => 'Add-on Signature',
            'is_required' => false,
            'min_selection' => 0,
            'max_selection' => 3,
        ]);
        foreach ([
            ['Extra Shot Espresso', 5000],
            ['Extra Syrup Vanilla', 4000],
            ['Gula Aren', 3000],
            ['Oat Milk', 8000],
            ['Whipped Cream', 5000],
        ] as [$name, $price]) {
            ProductModifier::query()->create([
                'modifier_group_id' => $addonGroup->id,
                'name' => $name,
                'price' => $price,
                'is_available' => true,
            ]);
        }

        $sugarGroup = ProductModifierGroup::query()->create([
            'name' => 'Level Gula',
            'is_required' => false,
            'min_selection' => 0,
            'max_selection' => 1,
        ]);
        foreach (['Normal', 'Less Sugar', 'No Sugar'] as $name) {
            ProductModifier::query()->create([
                'modifier_group_id' => $sugarGroup->id,
                'name' => $name,
                'price' => 0,
                'is_available' => true,
            ]);
        }

        foreach ($products as $product) {
            if (in_array($product->category_id, collect($categories)->only($drinkCategoryNames)->pluck('id')->all(), true)) {
                $product->modifierGroups()->sync([$addonGroup->id, $sugarGroup->id]);
            }
        }

        // ------------------------------------------------------------------
        // Stocks + Opening Stock Logs
        // ------------------------------------------------------------------
        foreach ($products as $productIndex => $product) {
            foreach ($productVariants[$product->id] ?? [] as $variant) {
                foreach ([$outletHQ, $outletBranch] as $outlet) {
                    $qty = 80 + (($productIndex * 13 + $variant->id * 7) % 60);
                    Stock::query()->create([
                        'outlet_id' => $outlet->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $qty,
                        'min_stock' => 10,
                    ]);

                    StockLog::query()->create([
                        'outlet_id' => $outlet->id,
                        'product_variant_id' => $variant->id,
                        'user_id' => $admin->id,
                        'type' => 'IN',
                        'qty_change' => $qty,
                        'qty_balance' => $qty,
                        'reference_number' => 'OPENING-' . $outlet->code,
                        'notes' => 'Saldo stok awal',
                        'created_at' => now()->subDays(14),
                    ]);
                }
            }
        }

        // ------------------------------------------------------------------
        // Customer Groups & Customers
        // ------------------------------------------------------------------
        $groupReguler = CustomerGroup::query()->create(['name' => 'Reguler', 'discount_percentage' => 0]);
        $groupMember = CustomerGroup::query()->create(['name' => 'Member', 'discount_percentage' => 5]);
        $groupVip = CustomerGroup::query()->create(['name' => 'VIP', 'discount_percentage' => 10]);

        $customers = [];
        foreach ([
            ['Budi Santoso', '081200000001', 'budi@example.com', $groupMember, 150],
            ['Sari Dewi', '081200000002', 'sari@example.com', $groupVip, 320],
            ['Andi Wijaya', '081200000003', null, $groupReguler, 0],
            ['Lina Marlina', '081200000004', 'lina@example.com', $groupMember, 75],
            ['Reza Pratama', '081200000005', null, $groupReguler, 0],
        ] as [$name, $phone, $email, $group, $points]) {
            $customers[] = Customer::query()->create([
                'customer_group_id' => $group->id,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'loyalty_points' => $points,
            ]);
        }

        // ------------------------------------------------------------------
        // Suppliers
        // ------------------------------------------------------------------
        foreach ([
            ['CV Sumber Kopi Nusantara', '081111222333', 'supplier@sumperkopinusantara.test', 'Jl. Raya Bogor No. 101, Jakarta Timur'],
            ['PT Dairy Fresh Indonesia', '081111222334', 'sales@dairyfresh.test', 'Kawasan Industri Pulo Gadung, Jakarta Timur'],
            ['Toko Bahan Kue Jaya', '081111222335', null, 'Pasar Santa Blok A-12, Jakarta Selatan'],
        ] as [$name, $phone, $email, $address]) {
            Supplier::query()->create([
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
            ]);
        }

        // ------------------------------------------------------------------
        // Vouchers
        // ------------------------------------------------------------------
        foreach ([
            ['KAFE10', 'Diskon 10% untuk semua menu', 'PERCENTAGE', 10, 50000, 25000, now()->subDays(30), now()->addYear()],
            ['HEMAT20', 'Potongan Rp 20.000', 'FIXED_AMOUNT', 20000, 100000, 0, now()->subDays(30), now()->addYear()],
            ['KAFEBARU', 'Selamat datang, pelanggan baru!', 'FIXED_AMOUNT', 15000, 75000, 0, now()->subDays(7), now()->addMonths(3)],
        ] as [$code, $name, $type, $value, $minPurchase, $maxDiscount, $from, $until]) {
            Voucher::query()->create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'min_purchase' => $minPurchase,
                'max_discount' => $maxDiscount,
                'valid_from' => $from,
                'valid_until' => $until,
                'is_active' => true,
            ]);
        }

        // ------------------------------------------------------------------
        // System Settings
        // ------------------------------------------------------------------
        $settings = [
            ['store_name', 'Kafeinarts', 'general'],
            ['tax_percentage', '11', 'general'],
            ['receipt_paper', '58', 'printer'],
            ['receipt_printer', 'XPrinter 58', 'printer'],
            ['auto_print_receipt', '0', 'printer'],
            ['payment_cash', '1', 'payment'],
            ['payment_qris', '1', 'payment'],
            ['payment_transfer', '1', 'payment'],
            ['payment_debit', '1', 'payment'],
            ['bank_name', 'BCA', 'payment'],
            ['bank_account_number', '1234567890', 'payment'],
            ['bank_account_name', 'Kafeinarts Coffee', 'payment'],
            ['qris_payload', '00020101021226670016ID.CO.QRIS.WWW01189360091400000000000002180210ID.CO.QRIS.WWW0303UMIK51440014ID.CO.QRIS.WWW0218ID1020230300001', 'payment'],
            ['loyalty_earn_per_amount', '10000', 'loyalty'],
            ['loyalty_point_value', '100', 'loyalty'],
        ];
        foreach ($settings as [$key, $value, $group]) {
            SystemSetting::put($key, $value, $group);
        }

        // ------------------------------------------------------------------
        // Expense & Income Categories (+ sample records)
        // ------------------------------------------------------------------
        $expenseCategories = [];
        foreach (['Operasional', 'Bahan Baku', 'Gaji', 'Listrik & Air', 'Perawatan', 'Perlengkapan', 'Lain-lain'] as $name) {
            $expenseCategories[$name] = ExpenseCategory::query()->create(['name' => $name]);
        }

        $incomeCategories = [];
        foreach (['Penjualan Lainnya', 'Jasa Brewing Class', 'Sewa Area', 'Lain-lain'] as $name) {
            $incomeCategories[$name] = IncomeCategory::query()->create(['name' => $name]);
        }

        foreach ([
            [$expenseCategories['Bahan Baku'], 450000, 'Pembelian biji kopi arabica 5 kg', 3],
            [$expenseCategories['Listrik & Air'], 850000, 'Tagihan listrik dan air bulanan', 1],
            [$expenseCategories['Operasional'], 75000, 'Isi ulang gas cafe', 0],
            [$expenseCategories['Perlengkapan'], 150000, 'Paper cup dan sedotan', 4],
        ] as [$category, $amount, $notes, $daysAgo]) {
            Expense::query()->create([
                'outlet_id' => $outletHQ->id,
                'expense_category_id' => $category->id,
                'user_id' => $managerUser->id,
                'amount' => $amount,
                'notes' => $notes,
                'expense_date' => now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        foreach ([
            [$incomeCategories['Jasa Brewing Class'], 300000, 'Brewing class untuk komunitas kopi', 2],
            [$incomeCategories['Penjualan Lainnya'], 275000, 'Penjualan merchandise luar sistem', 0],
        ] as [$category, $amount, $notes, $daysAgo]) {
            Income::query()->create([
                'outlet_id' => $outletHQ->id,
                'income_category_id' => $category->id,
                'user_id' => $managerUser->id,
                'amount' => $amount,
                'notes' => $notes,
                'income_date' => now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        // ------------------------------------------------------------------
        // Demo closed shift + sample transactions (last 6 days)
        // ------------------------------------------------------------------
        $demoShift = Shift::query()->create([
            'outlet_id' => $outletHQ->id,
            'user_id' => $kasir->id,
            'starting_cash' => 500000,
            'ending_cash' => 500000,
            'actual_cash' => 500000,
            'difference' => 0,
            'status' => 'CLOSED',
            'opened_at' => now()->subDays(6)->setTime(8, 0),
            'closed_at' => now()->subDay()->setTime(22, 0),
        ]);

        $price = fn (int $productIndex, int $variantIndex) => (float) ProductPrice::query()
            ->whereHas('variant', fn ($q) => $q->where('product_id', $products[$productIndex]->id))
            ->where('outlet_id', $outletHQ->id)
            ->orderBy('product_variant_id')
            ->skip($variantIndex)
            ->value('selling_price');

        $variantOf = fn (int $productIndex, int $variantIndex) => ProductVariant::query()
            ->where('product_id', $products[$productIndex]->id)
            ->orderBy('id')
            ->skip($variantIndex)
            ->first();

        $demoTransactions = [
            [6, '09:15', [[2, 0, 2]], 'CASH', null, null],                    // Cafe Latte x2
            [5, '11:30', [[13, 0, 1], [2, 0, 1]], 'QRIS', null, null],        // Croissant + Latte
            [5, '14:05', [[6, 0, 1]], 'CASH', null, null],                   // Kopi Gula Aren
            [4, '10:20', [[1, 0, 1], [16, 0, 1]], 'CASH', null, null],       // Americano + Cheese Stick
            [4, '16:45', [[3, 1, 1]], 'TRANSFER', 0, null],                  // Cappuccino Large (Budi)
            [3, '09:40', [[7, 0, 1], [13, 0, 1]], 'CASH', null, null],       // Matcha + Croissant
            [3, '13:10', [[0, 0, 1]], 'CASH', null, null],                  // Espresso
            [2, '12:00', [[12, 0, 1], [10, 0, 1]], 'CASH', null, 'KAFE10'], // Spaghetti + Lemon Tea
            [2, '17:30', [[11, 0, 2]], 'QRIS', null, null],                 // Thai Tea x2
            [1, '10:15', [[15, 0, 1], [8, 0, 1]], 'DEBIT', null, null],      // Fries + Chocolate
            [1, '19:20', [[14, 0, 1]], 'CASH', 1, null],                    // Chicken Sandwich (Sari)
            [0, '08:50', [[9, 1, 1]], 'CASH', null, null],                  // Vanilla Latte Large
            [0, '15:40', [[18, 0, 1]], 'TRANSFER', null, null],             // Beans 250g
            [6, '20:10', [[17, 0, 1]], 'CASH', null, null],                 // Tumbler
        ];

        $voucherKafe10 = Voucher::query()->where('code', 'KAFE10')->first();
        $cashSales = 0.0;
        $invoiceSequence = 1;

        foreach ($demoTransactions as [$daysAgo, $time, $items, $method, $customerIndex, $voucherCode]) {
            $transactionDate = now()->subDays($daysAgo)->setTimeFromTimeString($time)->second(0);
            $subtotal = 0.0;
            $itemPayloads = [];

            foreach ($items as [$productIndex, $variantIndex, $qty]) {
                $variant = $variantOf($productIndex, $variantIndex);
                $unitPrice = $price($productIndex, $variantIndex);
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;
                $itemPayloads[] = [$variant, $unitPrice, $qty, $lineTotal];
            }

            $discount = 0.0;
            $voucher = $voucherCode ? Voucher::query()->where('code', $voucherCode)->first() : null;
            if ($voucher) {
                $discount += $voucher->discountFor($subtotal);
            }

            if ($customerIndex !== null) {
                $customer = $customers[$customerIndex];
                $groupDiscount = (float) ($customer->group?->discount_percentage ?? 0);
                if ($groupDiscount > 0) {
                    $discount += ($subtotal - $discount) * ($groupDiscount / 100);
                }
            }

            $discount = min(round($discount, 2), $subtotal);
            $taxable = max(0, $subtotal - $discount);
            $tax = round($taxable * 0.11, 2);
            $grandTotal = round($taxable + $tax, 2);
            $invoice = 'INV-DEMO-' . str_pad((string) $invoiceSequence++, 4, '0', STR_PAD_LEFT);

            $transaction = Transaction::query()->create([
                'invoice_number' => $invoice,
                'outlet_id' => $outletHQ->id,
                'shift_id' => $demoShift->id,
                'user_id' => $kasir->id,
                'customer_id' => $customerIndex !== null ? $customers[$customerIndex]->id : null,
                'voucher_id' => $voucher?->id,
                'subtotal' => round($subtotal, 2),
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'status' => 'PAID',
                'notes' => 'Transaksi contoh (seed)',
                'transaction_date' => $transactionDate,
                'created_at' => $transactionDate,
                'updated_at' => $transactionDate,
            ]);

            $variantPrices = ProductPrice::query()->whereIn('product_variant_id', collect($itemPayloads)->pluck(0)->pluck('id'))
                ->where('outlet_id', $outletHQ->id)
                ->get()
                ->keyBy('product_variant_id');

            foreach ($itemPayloads as [$variant, $unitPrice, $qty, $lineTotal]) {
                $cost = (float) ($variantPrices[$variant->id]->cost_price ?? 0);
                $item = TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'product_variant_id' => $variant->id,
                    'cost_price' => $cost,
                    'selling_price' => $unitPrice,
                    'qty' => $qty,
                    'subtotal' => round($lineTotal, 2),
                    'discount' => 0,
                    'total' => round($lineTotal, 2),
                    'notes' => null,
                    'created_at' => $transactionDate,
                    'updated_at' => $transactionDate,
                ]);

                $stock = Stock::query()->where('outlet_id', $outletHQ->id)->where('product_variant_id', $variant->id)->lockForUpdate()->first();
                $stock->quantity = max(0, $stock->quantity - $qty);
                $stock->save();

                StockLog::query()->create([
                    'outlet_id' => $outletHQ->id,
                    'product_variant_id' => $variant->id,
                    'user_id' => $kasir->id,
                    'type' => 'OUT',
                    'qty_change' => -$qty,
                    'qty_balance' => $stock->quantity,
                    'reference_number' => $invoice,
                    'notes' => 'Penjualan (seed)',
                    'created_at' => $transactionDate,
                ]);
            }

            $change = $method === 'CASH' ? max(0, ceil($grandTotal / 50000) * 50000 - $grandTotal) : 0;
            $amountPaid = $method === 'CASH' ? $grandTotal + $change : $grandTotal;

            TransactionPayment::query()->create([
                'transaction_id' => $transaction->id,
                'payment_method' => $method,
                'amount_paid' => $amountPaid,
                'change_returned' => $change,
                'reference_note' => null,
                'created_at' => $transactionDate,
                'updated_at' => $transactionDate,
            ]);

            if ($method === 'CASH') {
                $cashSales += $grandTotal;
            }
        }

        $demoShift->update([
            'ending_cash' => 500000 + $cashSales,
            'actual_cash' => 500000 + $cashSales,
            'difference' => 0,
        ]);

        // ------------------------------------------------------------------
        // Audit Log seed entry
        // ------------------------------------------------------------------
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'event' => 'system.seed',
            'auditable_type' => self::class,
            'auditable_id' => 1,
            'old_values' => null,
            'new_values' => ['seeded_at' => now()->toDateTimeString()],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}
