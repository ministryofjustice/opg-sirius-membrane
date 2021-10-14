<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\ServiceStatusService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;
use Exception;

class ServiceStatusServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return ServiceStatusService
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ServiceStatusService
    {
        return new ServiceStatusService(
            $container->get(Logger::class),
            $container->get('ApplicationProxy'),
            $container->get('Config')
        );
    }
}
