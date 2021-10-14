<?php

namespace Application\Config;

use AwsModule\Session\SaveHandler\DynamoDb;

class SessionSaveHandlerConfigMapper
{
    public const CONFIG_CLASS_MAP =
        [
            'Aws\Session\SaveHandler\DynamoDb' => DynamoDb::class,
            'DynamoDb' => DynamoDb::class,
        ];

    public const ENV_VARIABLE_NAME = 'OPG_CORE_MEMBRANE_SESSION_SAVE_HANDLER';

    /*
     * The service that the encryption service decorates.
     * Must be a service that implements Laminas\Session\SaveHandler\SaveHandlerInterface.
     * Specifying 'null' uses default PHP Session Save Handler.
     *
     * @return string|null
     */
    public static function getConfig(): ?string
    {
        if (!getenv(self::ENV_VARIABLE_NAME)) {
            return null;
        }

        if (!isset(self::CONFIG_CLASS_MAP[getenv(self::ENV_VARIABLE_NAME)])) {
            throw new \InvalidArgumentException(self::ENV_VARIABLE_NAME . ' environment variable is invalid');
        }

        return self::CONFIG_CLASS_MAP[getenv(self::ENV_VARIABLE_NAME)];
    }
}
