<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\RequestService;
use Interop\Container\ContainerInterface;
use JwtLaminasAuth\Service\JwtService;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RequestServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return RequestService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): RequestService
    {
        return new RequestService($container->get(JwtService::class));
    }
}
