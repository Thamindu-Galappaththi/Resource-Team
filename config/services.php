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

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'tenant' => env('MICROSOFT_TENANT', 'organizations'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'slt_erp' => [
        'url' => env('SLT_ERP_URL', 'https://oneidentitytest.slt.com.lk/ERPAPIs/api/ERPData/GetAllEmployeeDetailsForServiceNo'),
        'username' => env('SLT_ERP_USERNAME'),
        'password' => env('SLT_ERP_PASSWORD'),
        // Local/testing cannot reach the intranet API. Production uses the live endpoint.
        'mock' => filter_var(env('SLT_ERP_MOCK', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOLEAN),
        'timeout' => (int) env('SLT_ERP_TIMEOUT', 8),
    ],

];
