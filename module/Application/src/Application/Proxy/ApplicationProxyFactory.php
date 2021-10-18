<?php

declare(strict_types=1);

namespace Application\Proxy;

use Application\Service\SiriusHttpClient;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ApplicationProxyFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return ApplicationProxy
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ApplicationProxy
    {
        /** @var SiriusHttpClient $httpClient */
        $httpClient = $container->get(SiriusHttpClient::class);

        return new ApplicationProxy(
            $container->get('config')['application'],
            $httpClient
        );
    }
}
