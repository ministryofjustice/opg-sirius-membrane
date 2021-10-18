<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\UserCreationService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserCreationServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return UserCreationService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserCreationService
    {
        $entityManager = $container->get('Doctrine\ORM\EntityManager');
        $userEmailService = $container->get('Application\Service\UserEmailService');

        return new UserCreationService($entityManager, $userEmailService);
    }
}
