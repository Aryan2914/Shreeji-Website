<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Razorpay API Configuration
    |--------------------------------------------------------------------------
    */

    'key_id' => env('RAZORPAY_KEY_ID', ''),
    'key_secret' => env('RAZORPAY_KEY_SECRET', ''),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'currency' => 'INR',
    'receipt_prefix' => 'SI_',

    // Auto-capture payments (vs. authorize-then-capture)
    'auto_capture' => true,

    // Payment page settings
    'checkout' => [
        'name' => 'Shreeji Infotech',
        'description' => 'Shreeji Infotech — Computer Hardware & Components',
        'image' => '', // Logo URL for Razorpay checkout modal
        'theme_color' => '#F97316', // Orange brand color
        'prefill_contact' => true,
        'prefill_email' => true,
    ],

    // Allowed payment methods
    'methods' => [
        'upi' => true,
        'card' => true,
        'netbanking' => true,
        'wallet' => true,
        'emi' => false,
        'paylater' => false,
    ],
];
