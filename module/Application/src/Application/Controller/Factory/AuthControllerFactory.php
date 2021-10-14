<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\AuthController;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class AuthControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     *
     * @return AuthController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthController
    {
        return new AuthController(
            $container->get('RequestService'),
            $container->get(AuthenticationServiceConstructor::class),
            $container->get('ApplicationProxy'),
            $container->get(Logger::class),
            $container->get(SecurityLogger::class)
        );
    }
}
