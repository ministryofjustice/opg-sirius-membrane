<?php

use Doctrine\DBAL\Driver\PDO\PgSql\Driver;

return [
    'doctrine' => [
        'annotations' => [
            'cache' => getenv("OPG_CORE_MEMRANE_CACHE_DOCTRINE_ANNOTATIONS") ? boolval(getenv("OPG_CORE_MEMRANE_CACHE_DOCTRINE_ANNOTATIONS")) : true,
            'path' => 'data/cache/annotations',
        ],
        'connection' => [
            // default connection name
            'orm_default' => [
                'driverClass' => Driver::class,
                'params' => [
                    'host' => getenv("OPG_CORE_MEMBRANE_DB_HOST")?: "localhost",
                    'port' => getenv("OPG_CORE_MEMBRANE_DB_PORT")? intval(getenv("OPG_CORE_MEMBRANE_DB_PORT")): 5432,
                    'user' => getenv("OPG_CORE_MEMBRANE_DB_USER")?: "opg",
                    'password' => getenv("OPG_CORE_MEMBRANE_DB_PASSWORD")?: "opg",
                    'dbname' => getenv("OPG_CORE_MEMBRANE_DB_NAME")?: "opg_membrane",
                ],
            ],
        ],
        'migrations_configuration' => [
            'orm_default' => [
                'table_storage' => [
                    'table_name' => 'membrane_migrations',
                    'version_column_name' => 'version',
                    'version_column_length' => 191,
                    'executed_at_column_name' => 'executed_at',
                    'execution_time_column_name' => 'execution_time',
                ],
                'migrations_paths' => [
                    'Migrations' => '/var/www/MembraneDoctrineMigrations',
                ],
                'all_or_nothing' => true,
            ],
        ],
    ],
];
