<?php

namespace App\Http\Requests\Seller;

use App\Models\Category;
use App\Models\Listing;
use App\Support\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a seller's listing, for both create and update.
 *
 * Price rules depend on the listing type, so those are applied in
 * `after()` rather than duplicated across two rule sets.
 */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('seller')?->isApproved() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'sku' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:5000'],

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'listing_type' => ['required', Rule::in(array_keys(Listing::typeLabels()))],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],

            'accepts_offers' => ['boolean'],
            'auto_accept' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'auto_decline' => ['nullable', 'numeric', 'min:0', 'max:999999', 'lte:auto_accept'],

            'shipping_mode' => ['required', Rule::in([
                Listing::SHIPPING_INHERIT,
                Listing::SHIPPING_FREE,
                Listing::SHIPPING_CUSTOM,
            ])],
            // Required only when the seller chose a flat price, so the
            // other two modes are not blocked by an empty box.
            'shipping_price' => [
                'nullable',
                'required_if:shipping_mode,'.Listing::SHIPPING_CUSTOM,
                'numeric',
                'min:0',
                'max:'.(config('verum_shipping.limits.max_price_cents') / 100),
            ],
            'currency' => ['required', 'string', 'size:3'],

            // Decimal input; converted to integer cents before saving.
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'start_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'reserve_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'bid_increment' => ['nullable', 'numeric', 'min:1', 'max:100000'],
            'auction_ends_at' => ['nullable', 'date', 'after:+1 hour'],

            'carat' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'dimensions' => ['nullable', 'string', 'max:80'],
            'shape' => ['nullable', 'string', 'max:60'],
            'cut' => ['nullable', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:60'],
            'clarity' => ['nullable', 'string', 'max:60'],
            'treatment' => ['nullable', 'string', 'max:120'],
            'untreated' => ['nullable', 'boolean'],
            'origin' => ['nullable', 'string', 'max:80'],
            'origin_country' => ['nullable', 'string', 'size:2', Rule::in(array_keys(Countries::all()))],

            'lab' => ['nullable', 'string', 'max:40'],
            'report_number' => ['nullable', 'string', 'max:60'],
            'report_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateCategoryIsLeaf($validator);
                $this->validatePricing($validator);
            },
        ];
    }

    /** A branch is for navigation; only a leaf can hold a listing. */
    private function validateCategoryIsLeaf(Validator $validator): void
    {
        $category = Category::query()->find($this->input('category_id'));

        if (! $category instanceof Category) {
            return;
        }

        if (! $category->isSelectable()) {
            $validator->errors()->add(
                'category_id',
                __('Choose a specific category, not a group.'),
            );
        }
    }

    private function validatePricing(Validator $validator): void
    {
        if ($this->input('listing_type') === Listing::TYPE_AUCTION) {
            $this->requirePresent($validator, 'start_price', __('An auction needs a starting price.'));
            $this->requirePresent($validator, 'auction_ends_at', __('An auction needs an end time.'));

            $start = (float) $this->input('start_price');
            $reserve = $this->input('reserve_price');

            if ($reserve !== null && $reserve !== '' && (float) $reserve < $start) {
                $validator->errors()->add(
                    'reserve_price',
                    __('The reserve cannot be below the starting price.'),
                );
            }

            return;
        }

        $this->requirePresent($validator, 'price', __('A fixed-price listing needs a price.'));
    }

    private function requirePresent(Validator $validator, string $field, string $message): void
    {
        $value = $this->input($field);

        if ($value === null || $value === '') {
            $validator->errors()->add($field, $message);
        }
    }
}
