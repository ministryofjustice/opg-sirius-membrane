<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\SessionRestController;
use Application\Service\SecurityLogger;
use Application\Service\UserSessionService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

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
            $container->get(UserSessionService::class),
            $container->get(Logger::class),
            $container->get(SecurityLogger::class),
        );
    }
}
