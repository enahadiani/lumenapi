<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'facebook' => [
        'client_id'     => env('FB_CLIENT_ID'),
        'client_secret' => env('FB_CLIENT_SECRET'),
        'redirect'      => env('FB_URL'),
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_KEY'),
    ],
    'midtrans' => [
        // Midtrans server key
        'serverKey'     => env('MIDTRANS_SERVERKEY'),
        // Midtrans client key
        'clientKey'     => env('MIDTRANS_CLIENTKEY'),
        // Isi false jika masih tahap development dan true jika sudah di production, default false (development)
        'isProduction'  => env('MIDTRANS_IS_PRODUCTION', false),
        'isSanitized'   => env('MIDTRANS_IS_SANITIZED', true),
        'is3ds'         => env('MIDTRANS_IS_3DS', true),                
    ],
    'gcm' => [
        // Midtrans server key
        'api_tarbak'     => env('GCM_APIKEY_TARBAK'),
        'api_ts'     => env('GCM_APIKEY_TS')     ,
        'api_siaga'     => env('GCM_APIKEY_SIAGA')                
    ],
    'api' => [
        'doc_url_siaga' => env('DOC_URL_SIAGA'),
        'redirect_email_url_siaga' => env('REDIRECT_EMAIL_URL_SIAGA')
    ],
    'civitax' => [
        'sso_auth_url' => env('CIVITAX_SSO_AUTH_URL'),
        'sso_role_url' => env('CIVITAX_SSO_ROLE_URL'),
        'sso_profile_url' => env('CIVITAX_SSO_PROFILE_URL'),
    ]

];