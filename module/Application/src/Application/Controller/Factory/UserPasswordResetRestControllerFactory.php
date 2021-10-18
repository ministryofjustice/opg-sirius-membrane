<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\UserPasswordResetRestController;
use Application\Service\SecurityLogger;
use Application\Service\UserPasswordResetService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserPasswordResetRestControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     *
     * @return UserPasswordResetRestController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserPasswordResetRestController
    {
        return new UserPasswordResetRestController(
            $container->get(UserPasswordResetService::class),
            $container->get(SecurityLogger::class)
        );
    }
}
