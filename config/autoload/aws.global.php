<?php

/*
 * For details on how to configure the AWS SDK please read
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html#credentials
 */
$config = [
    'aws' => [
        'debug' => filter_var(getenv('OPG_CORE_MEMBRANE_AWS_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        'endpoint' => getenv('OPG_CORE_MEMBRANE_SESSION_ENDPOINT_URL') ?: null,
        'region' => getenv('AWS_REGION') ?: "eu-west-1",
        'version' => 'latest',
    ],
];

/*
 * If credentials are available for this stack use them otherwise let it fallback to other auth mechanisms.
 *
 * If you don't provide a credentials option, the SDK attempts to load credentials from your environment in the
 * following order:
 * - Load credentials from environment variables.
 * - Load credentials from a credentials .ini file.
 * - Load credentials from an IAM role.
 */
if ('credentials' === getenv('OPG_CORE_MEMBRANE_AWS_AUTH_TYPE')
    && getenv('OPG_CORE_MEMBRANE_AWS_KEY')
    && getenv('OPG_CORE_MEMBRANE_AWS_SECRET')) {
    $credentials = new Aws\Credentials\Credentials(
        getenv('OPG_CORE_MEMBRANE_AWS_KEY'),
        getenv('OPG_CORE_MEMBRANE_AWS_SECRET')
    );

    $config['aws']['credentials'] = $credentials;
}

return $config;
