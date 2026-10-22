<?php

namespace App\Http\Requests\Orders;

use App\Support\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The delivery details for one order.
 *
 * Only the address and the buyer's choices are accepted here. Every
 * figure — price, postage, discount — is recomputed by PlaceOrder from
 * the seller's own rows, so nothing posted can change what is charged.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('buyer') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'line1' => ['required', 'string', 'max:160'],
            'line2' => ['nullable', 'string', 'max:160'],
            'city' => ['required', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys(Countries::all()))],
            'phone' => ['nullable', 'string', 'max:32'],

            // A name only. Which rate it maps to is looked up in the
            // seller's table, so an unknown value quotes nothing.
            'shipping_service' => ['nullable', 'string', 'max:32'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'line1' => __('address'),
            'line2' => __('address line 2'),
        ];
    }
}
