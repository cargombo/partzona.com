<?php

return [
    'merchant_id' => env('PAYRIFF_MERCHANT_ID'),
    'secret_key' => env('PAYRIFF_SECRET_KEY'),
    'api_url' => env('PAYRIFF_API_URL', 'https://api.payriff.com/api/v2'),
    'default_currency' => env('PAYRIFF_DEFAULT_CURRENCY', 'AZN'),
    'default_language' => env('PAYRIFF_DEFAULT_LANGUAGE', 'AZ'), // AZ, EN, RU

    'endpoints' => [
        'create_order' => '/createOrder',
        'get_status' => '/getStatusOrder',
    ],

    // Callback URL-lər üçün marşrut adları (route names)
    // Bunları öz marşrutlarınıza uyğun dəyişə bilərsiniz
    'approve_route_name' => 'payriff.callback.approve',
    'cancel_route_name' => 'payriff.callback.cancel',
    'decline_route_name' => 'payriff.callback.decline',
];
