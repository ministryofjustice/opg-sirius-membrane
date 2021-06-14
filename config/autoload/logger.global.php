<?php

use Laminas\Log\Writer\Stream;
use Laminas\Log\Logger;
use Laminas\Log\Formatter\Json;

return [
    'sirius_logger' => [
        // Convert PHP errors to exceptions.
        'errorsToExceptions' => false,

        // Logs local variables. Should be disabled for environments containing real user data (Pre-Prod, Prod) as it
        // may leak sensitive information.
        'logLocalVariables' => getenv('OPG_CORE_MEMBRANE_LOG_LOCAL_VARIABLES') ?
            boolval(getenv('OPG_CORE_MEMBRANE_LOG_LOCAL_VARIABLES')) : false,

        // will add the $logger object before the current PHP error handler
        'registerErrorHandler' => true, // errors logged to your writers
        'registerExceptionHandler' => true, // exceptions logged to your writers

        'writers' => [
            'stdout' => [
                'adapter' => Stream::class,
                'adapterOptions' => ['output' => "php://stderr"],
                'filter' => Logger::INFO,
                'enabled' => true,
                'formatter' => Json::class,
                'formatterOptions' => [],
            ],
        ],
    ],
];
