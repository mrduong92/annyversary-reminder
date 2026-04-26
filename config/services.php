<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'zalo' => [
        'oa_access_token' => env('ZALO_OA_ACCESS_TOKEN'),
        'zns_template_id' => env('ZALO_ZNS_TEMPLATE_ID'),
        'zns_endpoint' => env('ZALO_ZNS_ENDPOINT', 'https://business.openapi.zalo.me/message/template'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash-lite'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
    ],

    'speedsms' => [
        'access_token' => env('SPEEDSMS_ACCESS_TOKEN'),
        'sender' => env('SPEEDSMS_SENDER', 'VTDD'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

];
