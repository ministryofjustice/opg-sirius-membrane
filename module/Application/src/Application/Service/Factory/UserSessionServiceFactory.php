<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\AuthenticationServiceConstructor;
use Application\Service\UserSessionService;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use JwtLaminasAuth\Service\JwtService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;

class UserSessionServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|mixed[] $options
     *
     * @return UserSessionService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserSessionService
    {
        return new UserSessionService(
            $container->get(AuthenticationServiceConstructor::class),
            $container->get(SessionManager::class),
            $container->get(EntityManager::class),
            $container->get('config')['jwt_laminas_auth']['expiry'],
            $container->get(JwtService::class)
        );
    }
}
