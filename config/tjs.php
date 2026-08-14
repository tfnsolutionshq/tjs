<?php

return [
    'name' => env('TJS_NAME', 'TJS'),
    'full_name' => env('TJS_FULL_NAME', 'TFN Journal System'),
    'organization' => env('TJS_ORG', 'Turbo Flux Network Solutions'),
    'publisher' => env('TJS_PUBLISHER', 'Turbo Flux Network Solutions'),
    'default_license' => env('TJS_DEFAULT_LICENSE', 'CC BY 4.0'),
    'default_language' => 'en',
    'currency' => env('TJS_CURRENCY', 'NGN'),
    'membership' => [
        'platform_price' => (int) env('TJS_PLATFORM_MEMBERSHIP_PRICE', 15000),
        'platform_days' => (int) env('TJS_PLATFORM_MEMBERSHIP_DAYS', 365),
    ],
    /*
    | Standard article / journal licenses (see App\Support\Licenses).
    | Labels are stored in the database; URLs come from the catalog.
    */
];
