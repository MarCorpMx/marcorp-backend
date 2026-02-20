<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200',
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/([a-z0-9-]+\.)?punto-de-calma\.com$/',
        '/^https:\/\/([a-z0-9-]+\.)?marcorp\.mx$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
