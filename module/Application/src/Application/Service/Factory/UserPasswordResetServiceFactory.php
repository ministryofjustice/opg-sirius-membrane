<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\UserPasswordResetService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserPasswordResetServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return UserPasswordResetService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserPasswordResetService
    {
        $entityManager = $container->get('Doctrine\ORM\EntityManager');
        $userEmailService = $container->get('Application\Service\UserEmailService');

        return new UserPasswordResetService($entityManager, $userEmailService);
    }
}
