<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sender API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Sender API credentials. Only the API key is required.
    | The default endpoint (https://api.sender.net/v2) is used automatically.
    |
    */

    'api_key' => env('SENDER_API_KEY'),


    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how Sender integrates with Laravel's mail system.
    |
    */

    'mail' => [
        'transport' => 'sender',

        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Example App'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Options
    |--------------------------------------------------------------------------
    */

    'timeout' => env('SENDER_TIMEOUT', 30),

    'debug' => env('SENDER_DEBUG', false),

];
