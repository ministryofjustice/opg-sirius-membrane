<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\SecurityLogger;
use Interop\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SecurityLoggerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return SecurityLogger
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SecurityLogger
    {
        return new SecurityLogger($container->get(Logger::class));
    }
}
