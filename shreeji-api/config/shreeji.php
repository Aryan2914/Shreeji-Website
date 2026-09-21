<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shreeji Infotech Business Configuration
    |--------------------------------------------------------------------------
    |
    | Core business details used across invoices, notifications, and delivery.
    |
    */

    'company' => [
        'name' => 'Shreeji Infotech',
        'legal_name' => env('SHREEJI_LEGAL_NAME', 'Shreeji Infotech'),
        'gstin' => env('SHREEJI_GSTIN', '24AABCS1234F1Z5'),
        'pan' => env('SHREEJI_PAN', 'AABCS1234F'),
        'state' => 'Gujarat',
        'state_code' => '24', // Gujarat GST state code
        'email' => env('SHREEJI_EMAIL', 'sales@shreejiinfo.in'),
        'phone' => env('SHREEJI_PHONE', '+919377704344'),
        'website' => 'https://shreejiinfo.in',
    ],

    'address' => [
        'line_1' => env('SHREEJI_ADDRESS_LINE_1', 'Maninagar'),
        'line_2' => env('SHREEJI_ADDRESS_LINE_2', ''),
        'city' => 'Ahmedabad',
        'state' => 'Gujarat',
        'pincode' => '380008',
        'latitude' => 23.0069,
        'longitude' => 72.6069,
    ],

    /*
    |--------------------------------------------------------------------------
    | Express Delivery Configuration
    |--------------------------------------------------------------------------
    */

    'express' => [
        'enabled' => env('EXPRESS_DELIVERY_ENABLED', true),
        'label' => 'Ahmedabad Express',
        'min_minutes' => 30,
        'max_minutes' => 90,
        'same_day_cutoff' => '18:00', // Orders after this time = next day
        'operating_hours' => [
            'start' => '09:00',
            'end' => '20:00',
        ],
        'base_charge' => 49.00,
        'free_above' => 999.00, // Free express delivery above this order total
    ],

    'standard' => [
        'label' => 'Standard Delivery',
        'min_days' => 1,
        'max_days' => 3,
        'base_charge' => 0.00, // Free
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    */

    'invoice' => [
        'prefix' => 'SI',
        'terms' => 'Goods once sold will not be taken back. Warranty as per manufacturer policy.',
        'bank_name' => env('SHREEJI_BANK_NAME', ''),
        'bank_account' => env('SHREEJI_BANK_ACCOUNT', ''),
        'bank_ifsc' => env('SHREEJI_BANK_IFSC', ''),
        'bank_branch' => env('SHREEJI_BANK_BRANCH', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Configuration
    |--------------------------------------------------------------------------
    */

    'order' => [
        'number_prefix' => 'SI',
        'payment_timeout_minutes' => 30, // Auto-cancel unpaid orders after this
        'stock_reservation_minutes' => 15, // How long stock is held during checkout
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend URLs
    |--------------------------------------------------------------------------
    */

    'frontend' => [
        'url' => env('FRONTEND_URL', 'https://shop.shreejiinfo.in'),
        'order_tracking_path' => '/account/orders',
    ],
];
