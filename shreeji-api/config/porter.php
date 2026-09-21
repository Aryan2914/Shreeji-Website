<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Porter API Configuration (Phase 2)
    |--------------------------------------------------------------------------
    |
    | Porter is used for Ahmedabad express delivery (30-90 min).
    | https://porter.in/api-docs
    |
    */

    'api_key' => env('PORTER_API_KEY', ''),
    'base_url' => env('PORTER_BASE_URL', 'https://pfe-apigw-uat.porter.in'), // UAT/Sandbox
    'production_url' => 'https://pfe-apigw.porter.in',

    // Pickup location (Shreeji showroom)
    'pickup' => [
        'lat' => env('PORTER_PICKUP_LAT', 23.0069),
        'lng' => env('PORTER_PICKUP_LNG', 72.6069),
        'address' => 'Shreeji Infotech, Maninagar, Ahmedabad, Gujarat 380008',
        'contact_name' => env('PORTER_PICKUP_CONTACT', 'Shreeji Infotech'),
        'contact_phone' => env('PORTER_PICKUP_PHONE', '+919377704344'),
    ],

    // Webhook URL for delivery status updates
    'webhook_url' => env('PORTER_WEBHOOK_URL', ''),

    // Default vehicle type for electronics delivery
    'vehicle_type' => 'bike', // bike, 3-wheeler, mini-truck
];
