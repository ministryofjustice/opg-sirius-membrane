<?php

use JwtLaminasAuth\Authentication\Storage;
use Lcobucci\JWT\Signer\Hmac\Sha256;

return [
    'jwt_laminas_auth' => [
        // Choose signing method for the tokens
        'signer' => Sha256::class,
        /*
            You need to specify either a signing key or set read only to true.
            If tokens are read only, the implementation will not automatically
            refresh tokens which are close to expiry so you will need to handle
            this yourself.
        */
        'readOnly' => false,
        // Set the key to sign the token with, value is dependent on signer set.
        'signKey' => getenv('OPG_CORE_JWT_KEY') ? getenv('OPG_CORE_JWT_KEY') : 'SeCrEtKeYkNoWnOnLyToMe',
        // Set the key to verify the token with, value is dependent on signer set.
        'verifyKey' => getenv('OPG_CORE_JWT_KEY') ? getenv('OPG_CORE_JWT_KEY') : 'SeCrEtKeYkNoWnOnLyToMe',
        /*
            Default expiry for tokens. A token will expire after not being used
            for this number of seconds. A token which is used will automatically
            be extended provided a sign key is provided.
        */
        'expiry' => getenv('OPG_CORE_SESSION_TIMEOUT') ? intval(getenv('OPG_CORE_SESSION_TIMEOUT')) : 3600,
        'storage' => [
            'useChainAdaptor' => true,
            'adaptors' => [
                Storage\Header::class,
                Storage\Cookie::class,
            ],
        ],
    ],
];
