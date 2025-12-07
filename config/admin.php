<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Setup Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration setup for the admin variables stored in environment.
    | You can customize these settings as per your requirements.
    |
    */

    'name' => env('ADMIN_NAME', 'dummy'),
    'email' => env('ADMIN_EMAIL', 'dummy@dummy-domain.com'),
    'password' => env('ADMIN_PASSWORD', 'admin')
];
