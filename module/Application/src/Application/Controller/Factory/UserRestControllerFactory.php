<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\UserRestController;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserService;
use Application\Service\UserUpdateService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class UserRestControllerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|mixed[] $options
     *
     * @return UserRestController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserRestController
    {
        return new UserRestController(
            $container->get(AuthenticationServiceConstructor::class),
            $container->get(UserService::class),
            $container->get('Application\Service\UserCreationService'),
            $container->get(UserUpdateService::class),
            $container->get(Logger::class),
            $container->get(SecurityLogger::class)
        );
    }
}
