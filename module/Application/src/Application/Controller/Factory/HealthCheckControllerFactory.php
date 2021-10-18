<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\HealthCheckController;
use Application\Service\ServiceStatusService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class HealthCheckControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return HealthCheckController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HealthCheckController
    {
        return new HealthCheckController(
            $container->get('doctrine.connection.orm_default'),
            $container->get('ApplicationProxy'),
            $container->get(ServiceStatusService::class),
            $container->get('Config')
        );
    }
}
