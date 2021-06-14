<?php

declare(strict_types=1);

namespace Application\Controller\V1\Factory;

use Application\Controller\V1\SessionRestController;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserSessionService;
use Interop\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SessionRestControllerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|mixed[] $options
     *
     * @return SessionRestController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SessionRestController
    {
        return new SessionRestController(
            $container->get(AuthenticationServiceConstructor::class),
            $container->get(Logger::class),
            $container->get(SecurityLogger::class),
            $container->get(UserSessionService::class),
        );
    }
}
