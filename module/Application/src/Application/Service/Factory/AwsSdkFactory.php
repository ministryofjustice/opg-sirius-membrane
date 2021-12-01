<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Aws\Sdk;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AwsSdkFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array<mixed> $options
     * @return Sdk
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Sdk
    {
        $config = $container->get('Config');

        return new Sdk($config['aws']);
    }
}
