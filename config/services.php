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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'nabu' => [
        'base_url' => env('NABU_BASE_URL', 'https://gate.nabuxai.com'),
        'api_key' => env('NABU_API_KEY', '8280820Hh@'),
        'model' => env('NABU_MODEL', 'openrouter2/google/gemini-2.5-flash'),
    ],

    'clash_royale' => [
        'token' => env('CLASH_ROYALE_API_TOKEN'),
    ],

    'clash' => [
        'api_base' => env('CLASH_API_BASE') ?: 'https://api.clashofclans.com/v1/players/%23',
        'api_token' => env('CLASH_API_TOKEN') ?: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiIsImtpZCI6IjI4YTMxOGY3LTAwMDAtYTFlYi03ZmExLTJjNzQzM2M2Y2NhNSJ9.eyJpc3MiOiJzdXBlcmNlbGwiLCJhdWQiOiJzdXBlcmNlbGw6Z2FtZWFwaSIsImp0aSI6ImMxZGUyZDMwLWNkYWUtNGZlMy04MmZmLWI5NDVkYTRmZGE2ZSIsImlhdCI6MTc4NjkyMDk1Nywic3ViIjoiZGV2ZWxvcGVyL2UwNThjYWNiLTMyOGMtYzM2Zi1lZTBiLWQ3OTY1MDNjZDdiZiIsInNjb3BlcyI6WyJjbGFzaCJdLCJsaW1pdHMiOlt7InRpZXIiOiJkZXZlbG9wZXIvc2lsdmVyIiwidHlwZSI6InRocm90dGxpbmcifSx7ImNpZHJzIjpbIjY1LjEwOS4yMTkuMjUyIl0sInR5cGUiOiJjbGllbnQifV19.S0d_Np6ES9tpCXk2kjareeBOtjxPCulkF3FMauZKuyddEi8jNFupMZc0y2-glzZJJNlC30hh7uMvybSNgyNrzw',
    ],

];
