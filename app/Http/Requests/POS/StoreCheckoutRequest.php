<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*' => ['integer', 'exists:product_modifiers,id'],
            'payment_method' => ['required', 'in:CASH,QRIS,TRANSFER,DEBIT,CREDIT'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'voucher_code' => ['nullable', 'string', 'max:30'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'points_used' => ['nullable', 'integer', 'min:0'],
            'reference_note' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
