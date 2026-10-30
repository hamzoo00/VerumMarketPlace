<?php

namespace App\Http\Requests\Seller;

use App\Models\Category;
use App\Models\Listing;
use App\Support\Countries;
use App\Support\GemSpec;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a seller's listing, for both create and update.
 *
 * Price rules depend on the listing type, so those are applied in
 * `after()` rather than duplicated across two rule sets.
 *
 * Gem details are either a choice from the trade's own vocabulary or
 * free text that has to read like words, from App\Support\GemSpec —
 * the same lists and patterns the form and the browser check, so the
 * two can never disagree about what is acceptable.
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
            'title' => ['required', 'string', 'min:3', 'max:120', 'regex:'.GemSpec::regex('title')],
            'sku' => ['nullable', 'string', 'max:60', 'regex:'.GemSpec::regex('sku')],
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

            // Decimal input; converted to integer cents before saving. A
            // price of nothing is not a price.
            'price' => ['nullable', 'numeric', 'min:0.01', 'max:9999999', 'decimal:0,2'],
            'start_price' => ['nullable', 'numeric', 'min:0.01', 'max:9999999', 'decimal:0,2'],
            'reserve_price' => ['nullable', 'numeric', 'min:0.01', 'max:9999999', 'decimal:0,2'],
            'bid_increment' => ['nullable', 'numeric', 'min:1', 'max:100000', 'decimal:0,2'],
            'auction_ends_at' => ['nullable', 'date', 'after:+1 hour', 'before:+90 days'],

            'carat' => ['nullable', 'numeric', 'min:0.01', 'max:'.GemSpec::caratMax(), 'decimal:0,2'],
            'dimensions' => ['nullable', 'string', 'max:40', 'regex:'.GemSpec::regex('dimensions')],
            'shape' => ['nullable', Rule::in(array_keys(GemSpec::shapes()))],
            'cut' => ['nullable', Rule::in(array_keys(GemSpec::cuttingStyles()))],
            'color' => ['nullable', 'string', 'max:60', 'regex:'.GemSpec::regex('words')],
            'clarity' => ['nullable', Rule::in(GemSpec::clarityGrades())],
            'treatment' => ['nullable', 'string', 'max:120', 'regex:'.GemSpec::regex('phrase')],
            'untreated' => ['nullable', 'boolean'],
            'origin' => ['nullable', 'string', 'max:80', 'regex:'.GemSpec::regex('phrase')],
            'origin_country' => ['nullable', 'string', 'size:2', Rule::in(array_keys(Countries::all()))],

            'lab' => ['nullable', Rule::in(array_keys(GemSpec::labs()))],
            // A report number means something only alongside its lab.
            'report_number' => ['nullable', 'required_with:report_date', 'string', 'max:40', 'regex:'.GemSpec::regex('report_number')],
            'report_date' => ['nullable', 'date', 'before_or_equal:today', 'after:1950-01-01'],
        ];
    }

    /**
     * Messages that say what a good value looks like, not just that the
     * one given was wrong — "invalid format" helps nobody fix a field.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.regex' => __('Describe the piece in words, e.g. 2.14 ct unheated Mogok ruby.'),
            'sku.regex' => __('Letters, numbers, dashes and dots only.'),
            'dimensions.regex' => __('Write the measurements like 8.1 × 6.4 × 4.2 mm.'),
            'color.regex' => __('Describe the colour in words, e.g. Royal blue.'),
            'treatment.regex' => __('Describe the treatment in words, e.g. Heated or None detected.'),
            'origin.regex' => __('Name the place in words, e.g. Mogok.'),
            'report_number.regex' => __('Use the number exactly as printed on the report.'),
            'report_number.required_with' => __('Add the report number that goes with this date.'),
            'shape.in' => __('Choose a shape from the list.'),
            'cut.in' => __('Choose a cutting style from the list.'),
            'clarity.in' => __('Choose a clarity grade from the list.'),
            'lab.in' => __('Choose a laboratory from the list.'),
            'carat.min' => __('Carat weight must be more than zero.'),
            'carat.decimal' => __('Give the carat weight to two decimal places at most.'),
            'auction_ends_at.before' => __('An auction can run for 90 days at most.'),
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateCategoryIsLeaf($validator);
                $this->validatePricing($validator);
                $this->validateOffers($validator);
            },
        ];
    }

    /** An automatic yes above the asking price would accept more than was asked. */
    private function validateOffers(Validator $validator): void
    {
        if (! $this->boolean('accepts_offers') || $this->input('listing_type') === Listing::TYPE_AUCTION) {
            return;
        }

        $price = $this->input('price');
        $accept = $this->input('auto_accept');

        if (is_numeric($price) && is_numeric($accept) && (float) $accept > (float) $price) {
            $validator->errors()->add(
                'auto_accept',
                __('Auto-accept cannot be above your asking price.'),
            );
        }
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
