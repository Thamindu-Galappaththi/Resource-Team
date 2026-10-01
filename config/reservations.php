<?php

return [

    /*
    | Centre-local timezone used on reservation screens. Instants in
    | reservation_items.starts_at / ends_at are stored in UTC.
    */
    'display_timezone' => env('RESERVATION_TIMEZONE', 'Asia/Colombo'),

    'reference_prefix' => 'RRS',

];
