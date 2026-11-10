<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Throwable;

class DiscountVoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::query()->latest()->paginate(15);

        return view('promotion.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            Voucher::query()->create($data);

            return back()->with('swal_success', 'Voucher berhasil dibuat.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $this->validated($request);

        try {
            $voucher->update($data);

            return back()->with('swal_success', 'Voucher berhasil diperbarui.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function destroy(Voucher $voucher)
    {
        try {
            $voucher->delete();

            return back()->with('swal_success', 'Voucher berhasil dihapus.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Voucher sudah digunakan pada transaksi.');
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:vouchers,code'.($request->route('voucher') ? ','.$request->route('voucher')->id : '')],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:PERCENTAGE,FIXED_AMOUNT'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['min_purchase'] = (float) ($data['min_purchase'] ?? 0);
        $data['max_discount'] = (float) ($data['max_discount'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
