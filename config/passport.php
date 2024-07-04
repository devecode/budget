<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Personal Access Client
    |--------------------------------------------------------------------------
    |
    | The personal access client is used to generate personal access tokens
    | that allow users to make authenticated requests to your API without
    | providing any client ID or secret. You should configure a client
    | ID and secret here for the token generation to work correctly.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Password Grant Client
    |--------------------------------------------------------------------------
    |
    | The password grant client is used to generate access tokens for the
    | password grant type, which is mainly used for first-party clients.
    | You should configure a client ID and secret here for the token
    | generation to work correctly.
    |
    */

    'password_grant_client' => [
        'id' => env('PASSPORT_PASSWORD_GRANT_CLIENT_ID'),
        'secret' => env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET'),
    ],
];
