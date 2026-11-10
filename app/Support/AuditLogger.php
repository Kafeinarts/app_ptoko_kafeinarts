<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Outlet;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $event, string $type, int|string $id, ?array $old = null, ?array $new = null): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'event' => $event,
                'auditable_type' => $type,
                'auditable_id' => $id,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Audit logging must never interrupt the main operation.
        }
    }
}
