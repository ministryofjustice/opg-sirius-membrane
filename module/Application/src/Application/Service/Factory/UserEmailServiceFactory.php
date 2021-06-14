<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Service\UserEmailService;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class UserEmailServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return UserEmailService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UserEmailService
    {
        $userServiceConfig = $container->get('config')['user_service'];
        $logger = $container->get(Logger::class);

        $notifyApiKey = $container->get('config')['notify']['api_key'];
        $notifyClient = new NotifyClient([
            'apiKey' => $notifyApiKey,
            'httpClient' => new GuzzleClient()
        ]);

        return new UserEmailService($userServiceConfig, $notifyClient, $logger);
    }
}
