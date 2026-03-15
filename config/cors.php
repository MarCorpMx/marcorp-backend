<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://qa.agenda.marcorp.mx',
        'https://agenda.marcorp.mx',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:\d+$/',
        '/^https:\/\/localhost:\d+$/',
        '/^https:\/\/([a-z0-9-]+\.)?punto-de-calma\.com$/',
        '/^https:\/\/([a-z0-9-]+\.)?marcorp\.mx$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];