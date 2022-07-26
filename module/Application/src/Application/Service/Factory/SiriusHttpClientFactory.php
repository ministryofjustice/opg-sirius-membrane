<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\SiriusHttpClient;
use Interop\Container\ContainerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request as LaminasHttpRequest;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Log\Logger;

class SiriusHttpClientFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return SiriusHttpClient
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SiriusHttpClient
    {
        /** @var Logger $logger */
        $logger = $container->get(Logger::class);
        $config = $container->get('Config');

        $uri = $config['sirius_http_client']['uri'] ?? null;
        $clientOptions = $config['sirius_http_client']['options'] ?? [];

        $request = $this->generateRequest($container);

        // Set up client with new adapter and request to backend
        $client = new SiriusHttpClient($logger, $uri, $clientOptions);

        if (!empty($request)) {
            $client->setRequest($request);
        }

        return $this->setupClientAuth($client, $config);
    }

    /**
     * Sets Authentication on the client
     *
     * @param SiriusHttpClient $client
     * @param mixed[] $config
     * @return SiriusHttpClient
     */
    private function setupClientAuth(SiriusHttpClient $client, array $config): SiriusHttpClient
    {
        if (!empty($config['sirius_http_client']['username']) && !empty($config['sirius_http_client']['password'])) {
            $client->setAuth($config['sirius_http_client']['username'], $config['sirius_http_client']['password']);
        }

        return $client;
    }

    /*
     * Generates an HTTP request object to backend Sirius with appropriate headers
     */
    private function generateRequest(ContainerInterface $container): LaminasHttpRequest
    {
        /** @var LaminasHttpRequest $httpRequest */
        $httpRequest = $container->get('Request');

        if (!$httpRequest instanceof LaminasHttpRequest) {
            // if not an HttpRequest (i.e. a console request) we can't extract any headers from it,
            // so simply generate a blank HttpRequest object
            return new LaminasHttpRequest();
        }

        /** @var Headers $httpHeaders */
        $httpHeaders = $httpRequest->getHeaders();

        $config = $container->get('Config');

        $restRequest = new LaminasHttpRequest();
        $restRequest->setUri($config['sirius_http_client']['uri']);

        /** @var Headers $restHeaders */
        $restHeaders = $restRequest->getHeaders();
        $requestId = $httpHeaders->get('X-REQUEST-ID');

        if ($requestId instanceof HeaderInterface) {
            $restHeaders->addHeaderLine(
                'X-REQUEST-ID',
                $requestId->getFieldValue()
            );
        }

        $restRequest->setHeaders($restHeaders);

        return $restRequest;
    }
}
