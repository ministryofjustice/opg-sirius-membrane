<?php

// Use the $env value to determine which modules to load
$modules = [
    'Application',
    'JwtLaminasAuth',
    'DoctrineModule',
    'DoctrineORMModule',
    'Laminas\Router',
    'Laminas\I18n',
    'Laminas\ApiTools\ContentNegotiation',
];

if (getenv("OPG_CORE_MEMBRANE_PROFILING_MODULE_ENABLE") ?
    boolval(getenv("OPG_CORE_MEMBRANE_PROFILING_MODULE_ENABLE")) : false) {
    $modules[] = 'Profiling';
}

return [
    // This should be an array of module namespaces used in the application.
    'modules' => $modules,

    // These are various options for the listeners attached to the ModuleManager
    'module_listener_options' => [
        // This should be an array of paths in which modules reside.
        // If a string key is provided, the listener will consider that a module
        // namespace, the value of that key the specific path to that module's
        // Module class.
        'module_paths' => [
            './module',
            './vendor',
        ],

        // An array of paths from which to glob configuration files after
        // modules are loaded. These effectively override configuration
        // provided by modules themselves. Paths may use GLOB_BRACE notation.
        'config_glob_paths' => [
            'config/autoload/{,*.}{global,local}.php',
        ],

        // Whether or not to enable a configuration cache.
        // If enabled, the merged configuration will be cached and used in
        // subsequent requests.
        'config_cache_enabled' => getenv('OPG_CORE_MEMBRANE_CACHE_CONFIG') ? boolval(getenv('OPG_CORE_MEMBRANE_CACHE_CONFIG')) : true,

        // The key used to create the configuration cache file name.
        'config_cache_key' => 'application',

        // Whether or not to enable a module class map cache.
        // If enabled, creates a module class map cache which will be used
        // by in future requests, to reduce the autoloading process.
        'module_map_cache_enabled' => getenv('OPG_CORE_MEMBRANE_CACHE_MODULE_MAP') ? boolval(getenv('OPG_CORE_MEMBRANE_CACHE_MODULE_MAP')) : false,

        // The key used to create the class map cache file name.
        'module_map_cache_key' => 'application',

        // The path in which to cache merged configuration.
        'cache_dir' => '/tmp/config',
    ],
];
