<?php

use Laminas\Mvc\Application;

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (__FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

// Lt's have it here for now
/**
 * Turns warnings into exceptions
 *
 * @param int    $errno      Err no.
 *
 * @param string $errstr     Error message
 *
 * @param string $errfile    File name
 *
 * @param int    $errline    Err line
 *
 * @param string $errcontext Context
 *
 * @throws ErrorException
 */
function opgBackendErrorHandler($errno, $errstr, $errfile, $errline, $errcontext = null)
{
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('opgBackendErrorHandler');

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

if (! class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application.\n"
        . "- Type `composer install` if you are developing locally.\n"
    );
}

// Run the application!
Application::init(require __DIR__ . '/../config/application.config.php')->run();
