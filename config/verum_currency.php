<?php

/*
|--------------------------------------------------------------------------
| Display currency
|--------------------------------------------------------------------------
|
| Every price on the site is stored and charged in USD. A visitor may pick
| their country, and each price then also prints that country's amount in
| brackets — a courtesy conversion, never the amount transacted.
|
| Rates come from one provider, cached, with the last good set kept as a
| fallback. A conversion is only ever shown with the date it was fetched,
| so a stale figure can never pass as today's.
|
*/

return [

    // What the site actually charges in. Not visitor-selectable.
    'base' => 'USD',

    // Cookie holding the chosen country, in days. A year: a visitor's
    // country is not something they want to re-pick every week.
    'cookie' => 'verum_country',
    'cookie_days' => 365,

    'rates' => [
        /*
         * open.er-api.com: no key, ~160 currencies, which matters because
         * this trade runs through Sri Lanka, Thailand and Pakistan as much
         * as it does the US and EU.
         */
        'endpoint' => env('CURRENCY_RATES_URL', 'https://open.er-api.com/v6/latest/USD'),

        // The provider publishes daily and names its own next update, so
        // this is only the ceiling when that field is missing or absurd.
        'ttl_hours' => 12,

        // Beyond this a rate is too old to show at all: better to print
        // USD alone than a figure that may be badly wrong.
        'max_age_days' => 7,

        'timeout_seconds' => 8,
    ],

    /*
     * ISO 3166-1 alpha-2 => ISO 4217.
     *
     * Kept beside the country list rather than inside it: the dialling
     * code is a property of the country, whereas the currency is a
     * decision about what to quote, and several countries here share one
     * (the euro zone, and Ecuador which uses the US dollar outright).
     */
    'currencies' => [
        'AF' => 'AFN', 'AL' => 'ALL', 'DZ' => 'DZD', 'AR' => 'ARS', 'AM' => 'AMD',
        'AU' => 'AUD', 'AT' => 'EUR', 'AZ' => 'AZN', 'BH' => 'BHD', 'BD' => 'BDT',
        'BY' => 'BYN', 'BE' => 'EUR', 'BO' => 'BOB', 'BW' => 'BWP', 'BR' => 'BRL',
        'BG' => 'BGN', 'KH' => 'KHR', 'CM' => 'XAF', 'CA' => 'CAD', 'CL' => 'CLP',
        'CN' => 'CNY', 'CO' => 'COP', 'HR' => 'EUR', 'CZ' => 'CZK', 'DK' => 'DKK',
        'EC' => 'USD', 'EG' => 'EGP', 'EE' => 'EUR', 'ET' => 'ETB', 'FI' => 'EUR',
        'FR' => 'EUR', 'GE' => 'GEL', 'DE' => 'EUR', 'GH' => 'GHS', 'GR' => 'EUR',
        'HK' => 'HKD', 'HU' => 'HUF', 'IN' => 'INR', 'ID' => 'IDR', 'IR' => 'IRR',
        'IQ' => 'IQD', 'IE' => 'EUR', 'IL' => 'ILS', 'IT' => 'EUR', 'JP' => 'JPY',
        'JO' => 'JOD', 'KZ' => 'KZT', 'KE' => 'KES', 'KW' => 'KWD', 'KG' => 'KGS',
        'LA' => 'LAK', 'LV' => 'EUR', 'LB' => 'LBP', 'LT' => 'EUR', 'MG' => 'MGA',
        'MW' => 'MWK', 'MY' => 'MYR', 'MX' => 'MXN', 'MN' => 'MNT', 'MA' => 'MAD',
        'MZ' => 'MZN', 'MM' => 'MMK', 'NA' => 'NAD', 'NP' => 'NPR', 'NL' => 'EUR',
        'NZ' => 'NZD', 'NG' => 'NGN', 'NO' => 'NOK', 'OM' => 'OMR', 'PK' => 'PKR',
        'PE' => 'PEN', 'PH' => 'PHP', 'PL' => 'PLN', 'PT' => 'EUR', 'QA' => 'QAR',
        'RO' => 'RON', 'RU' => 'RUB', 'SA' => 'SAR', 'RS' => 'RSD', 'SG' => 'SGD',
        'SK' => 'EUR', 'ZA' => 'ZAR', 'KR' => 'KRW', 'ES' => 'EUR', 'LK' => 'LKR',
        'SE' => 'SEK', 'CH' => 'CHF', 'TW' => 'TWD', 'TJ' => 'TJS', 'TZ' => 'TZS',
        'TH' => 'THB', 'TN' => 'TND', 'TR' => 'TRY', 'TM' => 'TMT', 'UG' => 'UGX',
        'UA' => 'UAH', 'AE' => 'AED', 'GB' => 'GBP', 'US' => 'USD', 'UY' => 'UYU',
        'UZ' => 'UZS', 'VE' => 'VES', 'VN' => 'VND', 'ZM' => 'ZMW', 'ZW' => 'ZWL',
    ],

];
