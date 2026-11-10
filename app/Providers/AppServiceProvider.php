<?php

namespace App\Providers;

use App\Models\Outlet;
use App\Models\Stock;
use App\Support\OutletContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        app()->setLocale('id');

        View::composer('*', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $outletId = OutletContext::id();

            $view->with([
                'currentOutlet' => OutletContext::outlet(),
                'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
                'sidebarLowStock' => Stock::query()
                    ->where('outlet_id', $outletId)
                    ->whereColumn('quantity', '<=', 'min_stock')
                    ->count(),
            ]);
        });
    }
}
