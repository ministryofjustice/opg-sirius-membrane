<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Filter\Encrypt;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SessionEncryptionFilterFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return Encrypt
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Encrypt
    {
        $config = $container->get('Config');

        return new Encrypt($config['session']['encryption_filter']);
    }
}
