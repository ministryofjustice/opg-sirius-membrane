<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\SecurityLogger;
use Application\Service\UserUpdateService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserUpdateServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return UserUpdateService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserUpdateService
    {
        return new UserUpdateService(
            $container->get('Doctrine\ORM\EntityManager'),
            $container->get(SecurityLogger::class)
        );
    }
}
