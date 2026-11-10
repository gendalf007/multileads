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

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    | crmnew — отдельная интеграция отправки заявок в Contractor API
    | (POST {base_url}/api/contractor/applications, Bearer-токен подрядчика).
    | Токен и source_id можно переопределить по сайту в settings.crmnew.
    */
    'crmnew' => [
        'base_url' => env('CRMNEW_BASE_URL', 'http://crmnew.test'),
        'token' => env('CRMNEW_API_TOKEN'),
        'timeout' => (int) env('CRMNEW_TIMEOUT', 15),
        'entry_point' => env('CRMNEW_ENTRY_POINT', 'lead-form'),
    ],

];
