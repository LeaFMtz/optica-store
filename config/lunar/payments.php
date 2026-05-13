<?php

return [

    'default' => env('PAYMENTS_TYPE', 'mercadopago'),

    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'authorized' => 'payment-offline',
        ],
        'mercadopago' => [
            'driver' => 'mercadopago',
            'authorized' => 'payment-offline',
        ],
    ],

];
