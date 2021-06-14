<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\UserService;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return UserService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserService
    {
        $entityManager = $container->get(EntityManager::class);
        $userServiceConfig = $container->get('config')['user_service'];

        return new UserService($entityManager, $userServiceConfig);
    }
}
