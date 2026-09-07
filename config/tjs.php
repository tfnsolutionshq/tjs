<?php

return [
    'name' => env('TJS_NAME', 'TJS'),
    'full_name' => env('TJS_FULL_NAME', 'TFN Journal System'),
    'organization' => env('TJS_ORG', 'Turbo Flux Network Solutions'),
    'publisher' => env('TJS_PUBLISHER', 'Turbo Flux Network Solutions'),
    'developer_name' => env('TJS_DEVELOPER_NAME', 'TFNSolutions'),
    'developer_url' => env('TJS_DEVELOPER_URL', 'https://tfnsolutions.us'),
    'support' => [
        'email' => env('TJS_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@tfnsolutions.us')),
        'phone' => env('TJS_SUPPORT_PHONE'),
    ],
    'brand_icon' => env('TJS_BRAND_ICON', 'images/brand/tjs-icon-light.png'),
    'brand' => [
        'logo_light' => 'images/brand/tjs-logo-light.png',
        'logo_dark' => 'images/brand/tjs-logo-dark.png',
        'icon_light' => 'images/brand/tjs-icon-light.svg',
        'icon_dark' => 'images/brand/tjs-icon-dark.svg',
        'icon_light_png' => 'images/brand/tjs-icon-light.png',
        'icon_dark_png' => 'images/brand/tjs-icon-dark.png',
        'logo_master' => 'images/brand/tjs-logo.jpg',
    ],
    /*
    | Marketing copy (lead-approved).
    | - tagline: short product line
    | - pitch: homepage / public marketing sentence
    | - description: major product description (README / about)
    */
    'tagline' => 'TurboFlux Journal System (TJS): a modern publishing solution for anyone who publishes—built to simplify, professionalize, and scale the entire article publishing process.',
    'pitch' => 'TurboFlux Journal System (TJS): a modern publishing solution for anyone who publishes—built to simplify, professionalize, and scale the entire article publishing process.',
    'description' => 'TurboFlux Journal System (TJS) is a modern, all-in-one publishing platform designed for individual researchers, scholars, academics, authors, research groups, universities, academic departments, professional associations, learned societies, publishers, institutions, corporations, government agencies, NGOs, and other organisations that publish scholarly, scientific, technical, professional, or general-interest articles.

TJS streamlines the entire journal publishing lifecycle in one powerful platform—from article submission and editorial management to peer review, citation formatting, publication, DOI deposition, access-controlled full text, and long-term journal management. It supports multiple journals from a single platform, with customizable branding themes.',
    'default_license' => env('TJS_DEFAULT_LICENSE', 'CC BY 4.0'),
    'default_language' => 'en',
    'currency' => env('TJS_CURRENCY', 'NGN'),
    'membership' => [
        'platform_enabled' => filter_var(env('TJS_PLATFORM_MEMBERSHIP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'platform_price' => max(0, (int) (is_numeric(env('TJS_PLATFORM_MEMBERSHIP_PRICE')) ? env('TJS_PLATFORM_MEMBERSHIP_PRICE') : 15000)),
        'platform_days' => max(1, (int) (is_numeric(env('TJS_PLATFORM_MEMBERSHIP_DAYS')) ? env('TJS_PLATFORM_MEMBERSHIP_DAYS') : 365)),
        'enrollment_free_days' => max(1, (int) (is_numeric(env('TJS_JOURNAL_ENROLLMENT_MEMBERSHIP_DAYS')) ? env('TJS_JOURNAL_ENROLLMENT_MEMBERSHIP_DAYS') : 365)),
    ],
    /*
    | Annual (or custom-period) fee to list a journal publicly and unlock management.
    | When disabled, new member-created journals are waived; existing unpaid/expired stay as-is.
    */
    'journal_activation' => [
        'enabled' => filter_var(env('TJS_JOURNAL_ACTIVATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'price' => max(0, (int) (is_numeric(env('TJS_JOURNAL_ACTIVATION_PRICE')) ? env('TJS_JOURNAL_ACTIVATION_PRICE') : 50000)),
        'days' => max(1, (int) (is_numeric(env('TJS_JOURNAL_ACTIVATION_DAYS')) ? env('TJS_JOURNAL_ACTIVATION_DAYS') : 365)),
        'reminder_days' => [90, 60, 30, 7, 1, 0],
    ],
    'payments' => [
        // Unused at checkout: journal splits use Paystack split codes (SPL_). Kept for existing admin settings storage.
        'split_fee_percent' => max(0, min(100, (int) (is_numeric(env('TJS_PAYMENTS_SPLIT_FEE_PERCENT')) ? env('TJS_PAYMENTS_SPLIT_FEE_PERCENT') : 0))),
    ],
    'doi' => [
        'enabled' => filter_var(env('TJS_DOI_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'usd_to_ngn' => max(1, (int) (is_numeric(env('TJS_DOI_USD_TO_NGN')) ? env('TJS_DOI_USD_TO_NGN') : 2000)),
        'credit_price_usd' => max(0, (float) (is_numeric(env('TJS_DOI_CREDIT_PRICE_USD')) ? env('TJS_DOI_CREDIT_PRICE_USD') : 1)),
        'threshold_absolute' => max(0, (int) (is_numeric(env('TJS_DOI_THRESHOLD_ABSOLUTE')) ? env('TJS_DOI_THRESHOLD_ABSOLUTE') : 20)),
        'threshold_percent' => max(0, min(100, (int) (is_numeric(env('TJS_DOI_THRESHOLD_PERCENT')) ? env('TJS_DOI_THRESHOLD_PERCENT') : 10))),
        'platform_prefix' => env('TJS_DOI_PREFIX', '10.0000/tjs'),
        'crossref' => [
            'deposit_url' => env('CROSSREF_DEPOSIT_URL', 'https://doi.crossref.org/servlet/deposit'),
            'username' => env('CROSSREF_USERNAME'),
            'password' => env('CROSSREF_PASSWORD'),
            'depositor_name' => env('CROSSREF_DEPOSITOR_NAME', env('TJS_ORG', 'Turbo Flux Network Solutions')),
            'depositor_email' => env('CROSSREF_DEPOSITOR_EMAIL', env('MAIL_FROM_ADDRESS', 'doi@example.com')),
            'registrant' => env('CROSSREF_REGISTRANT', env('TJS_ORG', 'Turbo Flux Network Solutions')),
        ],
    ],
    /*
    | Standard article / journal licenses (see App\Support\Licenses).
    | Labels are stored in the database; URLs come from the catalog.
    */
];
