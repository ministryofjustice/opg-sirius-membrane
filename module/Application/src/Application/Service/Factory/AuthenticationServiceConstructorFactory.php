<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Authentication\Adapter\EnsureUser;
use Application\Authentication\Adapter\LockedUser;
use Application\Authentication\Storage\DoctrineUserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\Storage\Session;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthenticationServiceConstructorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return AuthenticationServiceConstructor
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthenticationServiceConstructor
    {
        $config = $container->get('Config');

        $doctrineAuthAdaptor = new EnsureUser($container->get('doctrine.authenticationadapter.orm_default'));

        $lockUserAdaptor = new LockedUser(
            $doctrineAuthAdaptor,
            $container->get(SecurityLogger::class),
            $config['user_service']['unsuccessful_login_attempts_permitted']
        );

        return new AuthenticationServiceConstructor(
            $container->get(DoctrineUserAccount::class),
            $container->get(Session::class),
            $lockUserAdaptor
        );
    }
}
