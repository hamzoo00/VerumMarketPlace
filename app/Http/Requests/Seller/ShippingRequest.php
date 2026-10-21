<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a seller's shipping settings and their rate rows.
 *
 * The form posts every rate at once, so a seller can add, edit and remove
 * options in one save rather than one round trip per row.
 */
class ShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('seller')?->isApproved() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $limits = config('verum_shipping.limits');
        $maxPrice = $limits['max_price_cents'] / 100;

        return [
            'handling_days' => ['required', 'integer', 'min:0', 'max:'.config('verum_shipping.handling.max_days')],
            'free_shipping_over' => ['nullable', 'numeric', 'min:0', 'max:'.$maxPrice],
            'shipping_note' => ['nullable', 'string', 'max:500'],

            'rates' => ['array', 'max:'.$limits['max_rates_per_seller']],
            'rates.*.service' => ['required', Rule::in(array_keys(config('verum_shipping.services')))],
            'rates.*.destination' => ['required', Rule::in(array_keys(config('verum_shipping.destinations')))],
            'rates.*.price' => ['required', 'numeric', 'min:0', 'max:'.$maxPrice],
            'rates.*.extra_item' => ['nullable', 'numeric', 'min:0', 'max:'.$maxPrice],
            'rates.*.transit_min_days' => ['required', 'integer', 'min:0', 'max:'.$limits['max_transit_days']],
            'rates.*.transit_max_days' => ['required', 'integer', 'min:0', 'max:'.$limits['max_transit_days']],
            'rates.*.tracked' => ['boolean'],
            'rates.*.insured' => ['boolean'],
            'rates.*.note' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->rejectBackwardsWindows($validator);
                $this->rejectDuplicateRates($validator);
            },
        ];
    }

    /** A window that ends before it starts would quote nonsense. */
    private function rejectBackwardsWindows(Validator $validator): void
    {
        foreach ($this->rateRows() as $index => $rate) {
            $min = (int) ($rate['transit_min_days'] ?? 0);
            $max = (int) ($rate['transit_max_days'] ?? 0);

            if ($max < $min) {
                $validator->errors()->add(
                    "rates.{$index}.transit_max_days",
                    __('The longest time cannot be shorter than the shortest.'),
                );
            }
        }
    }

    /**
     * The table has a unique index on (seller, service, destination); this
     * turns that constraint violation into a message the seller can act on.
     */
    private function rejectDuplicateRates(Validator $validator): void
    {
        $seen = [];

        foreach ($this->rateRows() as $index => $rate) {
            $key = ($rate['service'] ?? '').'|'.($rate['destination'] ?? '');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "rates.{$index}.service",
                    __('You already have a price for this service and destination.'),
                );

                continue;
            }

            $seen[$key] = true;
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rateRows(): array
    {
        $rows = $this->input('rates', []);

        return is_array($rows) ? $rows : [];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'handling_days' => __('dispatch time'),
            'free_shipping_over' => __('free postage threshold'),
            'rates.*.price' => __('price'),
            'rates.*.transit_min_days' => __('shortest delivery time'),
            'rates.*.transit_max_days' => __('longest delivery time'),
        ];
    }
}
