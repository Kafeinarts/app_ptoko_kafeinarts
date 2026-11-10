<?php

namespace App\Support;

use App\Models\Outlet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OutletContext
{
    public static function id(): int
    {
        $id = Session::get('outlet_id');

        if ($id && Outlet::query()->whereKey($id)->where('is_active', true)->exists()) {
            return (int) $id;
        }

        $user = Auth::user();
        $fallback = $user?->outlet_id ?? Outlet::query()->where('is_active', true)->value('id');

        if ($user && $user->isSuperAdmin()) {
            $fallback = $fallback ?: Outlet::query()->where('is_active', true)->value('id');
        }

        return (int) ($fallback ?? 1);
    }

    public static function outlet(): ?Outlet
    {
        return Outlet::query()->find(self::id());
    }

    public static function set(int $outletId): void
    {
        if (Outlet::query()->whereKey($outletId)->where('is_active', true)->exists()) {
            Session::put('outlet_id', $outletId);
        }
    }
}
