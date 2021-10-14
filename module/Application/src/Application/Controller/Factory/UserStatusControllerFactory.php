<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\UserStatusController;
use Application\Service\UserService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserStatusControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     *
     * @return UserStatusController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserStatusController
    {
        return new UserStatusController(
            $container->get(UserService::class)
        );
    }
}
