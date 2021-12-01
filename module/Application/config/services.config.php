<?php

use Application\Console;

return [
    'aliases' => [
        'translator' => 'MvcTranslator',
        'RequestService' => \Application\Service\RequestService::class,
        'ApplicationProxy' => \Application\Proxy\ApplicationProxy::class,
        'AuthenticationService' => 'Application\Service\AuthenticationService',
    ],
    'factories' => [
        \Laminas\Log\Logger::class => \Application\Service\Factory\LoggerServiceFactory::class,
        \Application\Service\SiriusHttpClient::class => \Application\Service\Factory\SiriusHttpClientFactory::class,
        \Application\Authentication\Storage\DoctrineUserAccount::class => \Application\Authentication\Storage\DoctrineUserAccountFactory::class,
        \Application\Service\ServiceStatusService::class => \Application\Service\Factory\ServiceStatusServiceFactory::class,
        \Application\Service\RequestService::class => \Application\Service\Factory\RequestServiceFactory::class,
        \Application\Service\UserService::class => \Application\Service\Factory\UserServiceFactory::class,
        \Application\Service\UserEmailService::class => \Application\Service\Factory\UserEmailServiceFactory::class,
        \Application\Service\UserCreationService::class => \Application\Service\Factory\UserCreationServiceFactory::class,
        \Application\Service\UserUpdateService::class => \Application\Service\Factory\UserUpdateServiceFactory::class,
        \Application\Service\UserPasswordResetService::class => \Application\Service\Factory\UserPasswordResetServiceFactory::class,
        \Application\Service\UserSessionService::class => \Application\Service\Factory\UserSessionServiceFactory::class,
        \Application\Service\AuthenticationServiceConstructor::class => \Application\Service\Factory\AuthenticationServiceConstructorFactory::class,
        \Application\Proxy\ApplicationProxy::class => \Application\Proxy\ApplicationProxyFactory::class,
        \Application\View\Strategy\XmlStrategy::class => \Application\View\XmlRendererFactory::class,
        \Laminas\Session\SessionManager::class => \Application\Service\Factory\SessionManagerFactory::class,
        \Laminas\Authentication\Storage\Session::class => \Application\Service\Factory\SessionStorageFactory::class,
        'SessionEncryptionFilter' => \Application\Service\Factory\SessionEncryptionFilterFactory::class,
        'SessionDecryptionFilter' => \Application\Service\Factory\SessionDecryptionFilterFactory::class,
        \Application\Session\SaveHandler\EncryptedSessionSaveHandler::class => \Application\Session\SaveHandler\Factory\EncryptedSessionSaveHandlerFactory::class,
        \Application\Service\SecurityLogger::class => \Application\Service\Factory\SecurityLoggerFactory::class,
        Console\ImportFixtures::class => Console\Factory\ImportFixturesFactory::class,
    ],
];
