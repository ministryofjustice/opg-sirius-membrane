<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Filter\Decrypt;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SessionDecryptionFilterFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return Decrypt
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Decrypt
    {
        $config = $container->get('Config');

        return new Decrypt($config['session']['encryption_filter']);
    }
}
