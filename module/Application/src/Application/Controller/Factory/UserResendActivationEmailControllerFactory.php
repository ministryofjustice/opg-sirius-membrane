<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\UserResendActivationEmailController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserResendActivationEmailControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     *
     * @return UserResendActivationEmailController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserResendActivationEmailController
    {
        $authenticationService = $container->get(\Application\Service\AuthenticationServiceConstructor::class);
        $userCreationService = $container->get('Application\Service\UserCreationService');

        return new UserResendActivationEmailController(
            $authenticationService,
            $userCreationService
        );
    }
}
