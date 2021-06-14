<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Storage\Session;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;

class SessionStorageFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return Session
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Session
    {
        $sessionManager = $container->get(SessionManager::class);

        return new Session(null, null, $sessionManager);
    }
}
