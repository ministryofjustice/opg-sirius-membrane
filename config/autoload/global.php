<?php

use Laminas\Http\Client\Adapter\Socket;

return [
    'application' => [
        'baseUri' => getenv('OPG_CORE_BACK_BACKEND_URI')? getenv('OPG_CORE_BACK_BACKEND_URI'): 'http://api',
    ],
    'http_client' => [
        'options' => [
            'timeout' => 180,
            'useragent' => 'Laminas',
        ],
    ],
    'sirius_http_client' => [
        'adapter' => Socket::class,
        'uri' => getenv('OPG_CORE_BACK_BACKEND_URI')? getenv('OPG_CORE_BACK_BACKEND_URI'): 'http://api',
        'options' => [
            'persistent' => false,
            'timeout' => 180,
        ],
    ],
];
