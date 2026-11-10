<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\IncomeController;
use App\Http\Controllers\Inventory\StockMovementController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ModifierController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\POS\CheckoutController;
use App\Http\Controllers\POS\ShiftController;
use App\Http\Controllers\POS\TransactionHistoryController;
use App\Http\Controllers\Promotion\DiscountVoucherController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Report\ExportReportController;
use App\Http\Controllers\Report\FinanceReportController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Setting\OutletController;
use App\Http\Controllers\Setting\RolePermissionController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Setting\SystemLogController;
use App\Http\Controllers\Setting\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::post('/switch-outlet', [OutletController::class, 'switch'])->name('outlets.switch');

    Route::prefix('pos')->name('pos.')->middleware('permission:pos.access')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('permission:pos.checkout')->name('checkout');
        Route::get('/receipt/{transaction}', [TransactionHistoryController::class, 'receipt'])->name('receipt');
    });

    Route::prefix('transactions')->name('pos.')->middleware('permission:pos.history')->group(function () {
        Route::get('/', [TransactionHistoryController::class, 'index'])->name('history');
        Route::get('/{transaction}', [TransactionHistoryController::class, 'show'])->name('history.show');
        Route::post('/{transaction}/void', [TransactionHistoryController::class, 'void'])->middleware('permission:pos.void')->name('history.void');
        Route::get('/{transaction}/return', [TransactionHistoryController::class, 'returnForm'])->middleware('permission:pos.return')->name('history.return');
        Route::post('/{transaction}/return', [TransactionHistoryController::class, 'returnStore'])->middleware('permission:pos.return')->name('history.return.store');
    });

    Route::prefix('shifts')->name('shifts.')->middleware('permission:shift.manage')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::post('/open', [ShiftController::class, 'open'])->name('open');
        Route::post('/cash-log', [ShiftController::class, 'cashLog'])->name('cash-log');
        Route::post('/close', [ShiftController::class, 'close'])->name('close');
        Route::get('/{shift}/receipt', [ShiftController::class, 'receipt'])->name('receipt');
    });

    Route::prefix('master')->name('master.')->group(function () {
        Route::middleware('permission:master.products')->group(function () {
            Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
            Route::get('products/template', [ProductController::class, 'template'])->name('products.template');
            Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
            Route::get('products/price-draft', [ProductController::class, 'priceDraft'])->name('products.price-draft');
            Route::resource('products', ProductController::class)->except(['show']);
        });

        Route::middleware('permission:master.categories')->group(function () {
            Route::get('categories/insert', [CategoryController::class, 'create'])->name('categories.insert');
            Route::get('categories/export', [CategoryController::class, 'export'])->name('categories.export');
            Route::get('categories/template', [CategoryController::class, 'template'])->name('categories.template');
            Route::post('categories/import', [CategoryController::class, 'import'])->name('categories.import');
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        });

        Route::middleware('permission:master.brands')->group(function () {
            Route::get('brands/insert', [BrandController::class, 'create'])->name('brands.insert');
            Route::get('brands/export', [BrandController::class, 'export'])->name('brands.export');
            Route::get('brands/template', [BrandController::class, 'template'])->name('brands.template');
            Route::post('brands/import', [BrandController::class, 'import'])->name('brands.import');
            Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
            Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
            Route::put('brands/{id}', [BrandController::class, 'update'])->name('brands.update');
            Route::delete('brands/{id}', [BrandController::class, 'destroy'])->name('brands.destroy');
        });

        Route::middleware('permission:master.units')->group(function () {
            Route::get('units/insert', [UnitController::class, 'create'])->name('units.insert');
            Route::get('units/export', [UnitController::class, 'export'])->name('units.export');
            Route::get('units/template', [UnitController::class, 'template'])->name('units.template');
            Route::post('units/import', [UnitController::class, 'import'])->name('units.import');
            Route::get('units', [UnitController::class, 'index'])->name('units.index');
            Route::post('units', [UnitController::class, 'store'])->name('units.store');
            Route::put('units/{id}', [UnitController::class, 'update'])->name('units.update');
            Route::delete('units/{id}', [UnitController::class, 'destroy'])->name('units.destroy');
        });

        Route::middleware('permission:master.customers')->group(function () {
            Route::get('customers/insert', [CustomerController::class, 'create'])->name('customers.insert');
            Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
            Route::get('customers/template', [CustomerController::class, 'template'])->name('customers.template');
            Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::put('customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
            Route::delete('customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        });

        Route::middleware('permission:master.suppliers')->group(function () {
            Route::get('suppliers/insert', [SupplierController::class, 'create'])->name('suppliers.insert');
            Route::get('suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
            Route::get('suppliers/template', [SupplierController::class, 'template'])->name('suppliers.template');
            Route::post('suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
            Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::put('suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
            Route::delete('suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        });

        Route::middleware('permission:master.modifiers')->group(function () {
            Route::get('modifiers/insert', [ModifierController::class, 'create'])->name('modifiers.insert');
            Route::get('modifiers/export', [ModifierController::class, 'export'])->name('modifiers.export');
            Route::get('modifiers/template', [ModifierController::class, 'template'])->name('modifiers.template');
            Route::post('modifiers/import', [ModifierController::class, 'import'])->name('modifiers.import');
            Route::get('modifiers', [ModifierController::class, 'index'])->name('modifiers.index');
            Route::post('modifiers/groups', [ModifierController::class, 'storeGroup'])->name('modifiers.groups.store');
            Route::put('modifiers/groups/{group}', [ModifierController::class, 'updateGroup'])->name('modifiers.groups.update');
            Route::delete('modifiers/groups/{group}', [ModifierController::class, 'destroyGroup'])->name('modifiers.groups.destroy');
            Route::post('modifiers/groups/{group}/items', [ModifierController::class, 'storeModifier'])->name('modifiers.items.store');
            Route::delete('modifiers/{modifier}', [ModifierController::class, 'destroyModifier'])->name('modifiers.items.destroy');
        });
    });

    Route::prefix('inventory')->name('inventory.')->middleware('permission:inventory.view')->group(function () {
        Route::get('stocks', [StockMovementController::class, 'index'])->name('stocks');
        Route::get('stocks/adjust', [StockMovementController::class, 'create'])->middleware('permission:inventory.adjust')->name('stocks.create');
        Route::post('stocks/adjust', [StockMovementController::class, 'store'])->middleware('permission:inventory.adjust')->name('stocks.store');

        Route::middleware('permission:inventory.opname')->group(function () {
            Route::get('opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
            Route::get('opnames/create', [StockOpnameController::class, 'create'])->name('opnames.create');
            Route::post('opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
            Route::get('opnames/{opname}', [StockOpnameController::class, 'show'])->name('opnames.show');
        });

        Route::middleware('permission:inventory.transfer')->group(function () {
            Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
            Route::get('transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
            Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');
            Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
        });
    });

    Route::prefix('purchasing')->name('purchasing.')->middleware('permission:purchasing.view')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->middleware('permission:purchasing.manage')->name('create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('permission:purchasing.manage')->name('store');
        Route::get('/{purchase}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::post('/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:purchasing.manage')->name('receive');
        Route::post('/{purchase}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:purchasing.manage')->name('cancel');
        Route::post('/{purchase}/return', [PurchaseOrderController::class, 'returnStore'])->middleware('permission:purchasing.manage')->name('return');
    });

    Route::prefix('promotions')->name('promotions.')->middleware('permission:promotion.manage')->group(function () {
        Route::get('/', [DiscountVoucherController::class, 'index'])->name('index');
        Route::post('/', [DiscountVoucherController::class, 'store'])->name('store');
        Route::put('/{voucher}', [DiscountVoucherController::class, 'update'])->name('update');
        Route::delete('/{voucher}', [DiscountVoucherController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('finance')->name('finance.')->middleware('permission:finance.view')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses');
        Route::post('expenses', [ExpenseController::class, 'store'])->middleware('permission:finance.manage')->name('expenses.store');
        Route::post('expenses/categories', [ExpenseController::class, 'storeCategory'])->middleware('permission:finance.manage')->name('expenses.categories');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('permission:finance.manage')->name('expenses.destroy');

        Route::get('incomes', [IncomeController::class, 'index'])->name('incomes');
        Route::post('incomes', [IncomeController::class, 'store'])->middleware('permission:finance.manage')->name('incomes.store');
        Route::post('incomes/categories', [IncomeController::class, 'storeCategory'])->middleware('permission:finance.manage')->name('incomes.categories');
        Route::delete('incomes/{income}', [IncomeController::class, 'destroy'])->middleware('permission:finance.manage')->name('incomes.destroy');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [SalesReportController::class, 'index'])->middleware('permission:report.sales')->name('sales');
        Route::get('finance', [FinanceReportController::class, 'index'])->middleware('permission:report.finance')->name('finance');

        Route::middleware('permission:report.export')->group(function () {
            Route::get('sales/excel', [ExportReportController::class, 'salesExcel'])->name('sales.excel');
            Route::get('sales/pdf', [ExportReportController::class, 'salesPdf'])->name('sales.pdf');
            Route::get('finance/pdf', [ExportReportController::class, 'financePdf'])->name('finance.pdf');
        });
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('permission:settings.users')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        Route::middleware('permission:settings.roles')->group(function () {
            Route::get('roles', [RolePermissionController::class, 'index'])->name('roles');
            Route::put('roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
        });

        Route::middleware('permission:settings.outlets')->group(function () {
            Route::get('outlets', [OutletController::class, 'index'])->name('outlets');
            Route::post('outlets', [OutletController::class, 'store'])->name('outlets.store');
            Route::put('outlets/{outlet}', [OutletController::class, 'update'])->name('outlets.update');
            Route::delete('outlets/{outlet}', [OutletController::class, 'destroy'])->name('outlets.destroy');
        });

        Route::get('general', [SettingController::class, 'index'])->middleware('permission:settings.general')->name('general');
        Route::put('general', [SettingController::class, 'update'])->middleware('permission:settings.general')->name('general.update');

        Route::get('logs', [SystemLogController::class, 'index'])->middleware('permission:settings.logs')->name('logs');
    });
});
