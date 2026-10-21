<?php

namespace App\Http\Requests\Seller;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates one discount code.
 *
 * The value fields are required only for the type that uses them, so a
 * free-postage code is not blocked by an empty percent box.
 */
class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('seller')?->isApproved() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $limits = config('verum_offers.coupons');
        $sellerId = $this->user('seller')?->storefront?->id;

        return [
            'code' => [
                'required',
                'string',
                "min:{$limits['code_min']}",
                "max:{$limits['code_max']}",
                // Alphanumeric only: a code is read aloud and typed in.
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('coupons', 'code')
                    ->where('seller_id', $sellerId)
                    // Null on create; the bound model's key on update.
                    ->ignore($this->editing()?->getKey()),
            ],
            'type' => ['required', Rule::in(array_keys($limits['types']))],

            'percent' => [
                'nullable',
                'required_if:type,'.Coupon::TYPE_PERCENT,
                'integer',
                'min:1',
                "max:{$limits['max_percent']}",
            ],
            'amount' => [
                'nullable',
                'required_if:type,'.Coupon::TYPE_FIXED,
                'numeric',
                'min:0.01',
                'max:'.($limits['max_fixed_cents'] / 100),
            ],

            'min_spend' => ['nullable', 'numeric', 'min:0', 'max:'.($limits['max_fixed_cents'] / 100)],
            'max_redemptions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_buyer_limit' => ['required', 'integer', 'min:1', 'max:100'],

            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    /** The coupon being updated, or null when creating one. */
    private function editing(): ?Coupon
    {
        $bound = $this->route('coupon');

        return $bound instanceof Coupon ? $bound : null;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use letters and numbers only — no spaces or punctuation.'),
            'code.unique' => __('You already have a code with that name.'),
            'ends_at.after' => __('The end date must come after the start date.'),
        ];
    }
}
