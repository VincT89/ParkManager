<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parking Platforms Integration
    |--------------------------------------------------------------------------
    */

    'parkos' => [
        'enabled' => env('PARKOS_ENABLED', false),
        'base_url' => env('PARKOS_BASE_URL', 'https://api.parkos.com/v1'),
        'api_key' => env('PARKOS_API_KEY'),
        'timeout' => env('PARKOS_TIMEOUT', 15),
        'fixture_mode' => env('PARKOS_FIXTURE_MODE', false),
        'fixture_file' => env('PARKOS_FIXTURE_FILE', 'reservations_success.json'),
    ],

    'my_parking' => [
        'enabled' => env('MY_PARKING_ENABLED', false),
        'base_url' => env('MY_PARKING_BASE_URL', 'https://api.myparking.it/v1'),
        'api_key' => env('MY_PARKING_API_KEY'),
        'timeout' => env('MY_PARKING_TIMEOUT', 15),
        'fixture_mode' => env('MY_PARKING_FIXTURE_MODE', false),
        'fixture_file' => env('MY_PARKING_FIXTURE_FILE', 'reservations_success.json'),
    ],

    'vologio' => [
        'enabled' => env('VOLOGIO_ENABLED', false),
        'base_url' => env('VOLOGIO_BASE_URL', 'https://api.vologio.it/v1'),
        'api_key' => env('VOLOGIO_API_KEY'),
        'timeout' => env('VOLOGIO_TIMEOUT', 15),
        'fixture_mode' => env('VOLOGIO_FIXTURE_MODE', false),
        'fixture_file' => env('VOLOGIO_FIXTURE_FILE', 'reservations_success.json'),
    ],

    'parking_my_car' => [
        'enabled' => env('PARKING_MY_CAR_ENABLED', false),
        'base_url' => env('PARKING_MY_CAR_BASE_URL', 'https://api.parkingmycar.it/v1'),
        'api_key' => env('PARKING_MY_CAR_API_KEY'),
        'timeout' => env('PARKING_MY_CAR_TIMEOUT', 15),
        'fixture_mode' => env('PARKING_MY_CAR_FIXTURE_MODE', false),
        'fixture_file' => env('PARKING_MY_CAR_FIXTURE_FILE', 'reservations_success.json'),
    ],

];
