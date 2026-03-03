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

    'apix' => [
        'callback_url' => env('APIX_CALLBACK_URL', 'https://api.agecontrole.com.br/api/webhook/apix'),
        'website' => env('APIX_WEBSITE_URL', 'https://api.agecontrole.com.br'),
    ],

    'd4sign' => [
        'token_api' => env('D4SIGN_TOKEN_API'),
        'crypt_key' => env('D4SIGN_CRYPT_KEY'),
        'base_url' => env('D4SIGN_BASE_URL', 'https://sandbox.d4sign.com.br/api/v1'),
        'uuid_safe' => env('D4SIGN_UUID_SAFE'),
        'webhook_url' => env('D4SIGN_WEBHOOK_URL'), // Opcional: override da URL do webhook (ex: https://seu-dominio.com/api/webhook/d4sign)
    ],

];
