<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->get('q'), fn ($q, $v) => $q->where('event', 'like', "%$v%"))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('setting.logs', compact('logs'));
    }
}
