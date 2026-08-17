<?php

return [
    'default' => env('DEFAULT_PAYMENT_GATEWAY', 'zibal'),

    'currency' => 'IRT', // IRT = Toman

    'gateways' => [
        'zibal' => [
            'merchant' => env('ZIBAL_MERCHANT', 'zibal'),
            'request_url' => 'https://gateway.zibal.ir/v1/request',
            'verify_url' => 'https://gateway.zibal.ir/v1/verify',
            'start_pay_url' => 'https://gateway.zibal.ir/start/',
            'callback_url' => env('APP_URL') . '/subscription/callback/zibal',
        ],

        'payping' => [
            'token' => env('PAYPING_TOKEN', 'test_payping_token'),
            'request_url' => 'https://api.payping.net/v2/pay',
            'verify_url' => 'https://api.payping.net/v2/pay/verify',
            'start_pay_url' => 'https://api.payping.net/v2/pay/gotoipg/',
            'callback_url' => env('APP_URL') . '/subscription/callback/payping',
        ],

        'zarinpal' => [
            'merchant_id' => env('ZARINPAL_MERCHANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
            'request_url' => 'https://payment.zarinpal.com/pg/v4/payment/request.json',
            'verify_url' => 'https://payment.zarinpal.com/pg/v4/payment/verify.json',
            'start_pay_url' => 'https://payment.zarinpal.com/pg/StartPay/',
            'callback_url' => env('APP_URL') . '/subscription/callback/zarinpal',
        ],
    ],
];
