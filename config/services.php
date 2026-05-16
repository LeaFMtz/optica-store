<?php

declare(strict_types=1);

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'public_key' => env('STRIPE_KEY'),
        'key' => env('STRIPE_SECRET'),
    ],

    'mercadopago' => [
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'api_mode' => env('MERCADOPAGO_API_MODE', 'orders'),
    ],

    'zipnova' => [
        'token' => env('ZIPNOVA_API_TOKEN'),
        'secret' => env('ZIPNOVA_API_SECRET'),
        'account_id' => env('ZIPNOVA_ACCOUNT_ID'),
        'origin_id' => env('ZIPNOVA_ORIGIN_ID'),
        'base_url' => env('ZIPNOVA_BASE_URL', 'https://api.zipnova.com.ar'),
        'mock' => env('ZIPNOVA_MOCK', false),
        'default_package' => [
            'weight_grams' => env('ZIPNOVA_DEFAULT_WEIGHT_GRAMS', 500),
            'height_cm' => env('ZIPNOVA_DEFAULT_HEIGHT_CM', 10),
            'width_cm' => env('ZIPNOVA_DEFAULT_WIDTH_CM', 10),
            'length_cm' => env('ZIPNOVA_DEFAULT_LENGTH_CM', 15),
            'classification_id' => env('ZIPNOVA_DEFAULT_CLASSIFICATION_ID', 1),
        ],
    ],

];
