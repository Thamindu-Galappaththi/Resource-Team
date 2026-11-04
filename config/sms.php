<?php

return [
    /*
    | "log" writes messages to the application log (development default).
    | "http" posts JSON {to, message, sender_id} to SMS_HTTP_URL with a bearer token.
    */
    'driver' => env('SMS_DRIVER', 'log'),
    'sender_id' => env('SMS_SENDER_ID', 'NEBULA'),
    'country_code' => env('SMS_COUNTRY_CODE', '94'),

    'http' => [
        'url' => env('SMS_HTTP_URL'),
        'token' => env('SMS_HTTP_TOKEN'),
        'timeout' => (int) env('SMS_HTTP_TIMEOUT', 10),
    ],
];
