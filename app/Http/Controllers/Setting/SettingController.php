<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Throwable;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::query()->orderBy('group')->get()->groupBy('group');

        return view('setting.general', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['nullable', 'string', 'max:100'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loyalty_earn_per_amount' => ['nullable', 'numeric', 'min:0'],
            'loyalty_point_value' => ['nullable', 'numeric', 'min:0'],
            'receipt_paper' => ['nullable', 'in:58,80'],
            'receipt_printer' => ['nullable', 'string', 'max:100'],
            'auto_print_receipt' => ['nullable', 'boolean'],
            'payment_cash' => ['nullable', 'boolean'],
            'payment_qris' => ['nullable', 'boolean'],
            'payment_transfer' => ['nullable', 'boolean'],
            'payment_debit' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'qris_payload' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            foreach ($data as $key => $value) {
                SystemSetting::put($key, in_array($key, ['auto_print_receipt', 'payment_cash', 'payment_qris', 'payment_transfer', 'payment_debit'], true)
                    ? (string) ($request->boolean($key) ? 1 : 0)
                    : (string) $value, $this->groupFor($key));
            }

            return back()->with('swal_success', 'Pengaturan berhasil disimpan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    protected function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'payment_'), str_starts_with($key, 'bank_'), $key === 'qris_payload' => 'payment',
            str_starts_with($key, 'receipt_'), $key === 'auto_print_receipt' => 'printer',
            str_starts_with($key, 'loyalty_') => 'loyalty',
            default => 'general',
        };
    }
}
