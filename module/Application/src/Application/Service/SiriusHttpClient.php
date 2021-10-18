<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Http\Client as LaminasHttpClient;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Log\Logger;

class SiriusHttpClient extends LaminasHttpClient
{
    const REQUEST_LOG_MESSAGE = 'Making HTTP request: %s %s';
    const RESPONSE_LOG_MESSAGE = '%s Response received: %s %s (%d bytes)';

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param string|null $uri
     * @param mixed[]|null $options
     */
    public function __construct(Logger $logger, string $uri = null, array $options = null)
    {
        parent::__construct($uri, $options);

        $this->logger = $logger;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function send(Request $request = null): Response
    {
        // This is to work around the fact that Laminas populates an internal $request object, and you can also send through $request here.
        // We always want $request to be a real object, so this takes care of that.
        if ($request === null) {
            $request = $this->getRequest();
        }

        $preRequest = microtime(true);

        $this->logger->info(
            sprintf(self::REQUEST_LOG_MESSAGE, $this->getMethod(), $this->getUri()->toString()),
            ['category' => 'HTTP_CLIENT']
        );

        // The actual HTTP-Header sent to the frontend is: "X-Sirius-Blackfiretrigger" but nginx config converts it to the HTTP_X format before sending to php-fpm
        if (isset($_SERVER['HTTP_X_SIRIUS_BLACKFIRETRIGGER']) && $_SERVER['HTTP_X_SIRIUS_BLACKFIRETRIGGER'] === 'true') {
            /** @var Headers $existingHeaders */
            $existingHeaders = $request->getHeaders();
            $existingHeaders->addHeader(new GenericHeader('X-Sirius-Blackfiretrigger', 'true'));
            $request->setHeaders($existingHeaders);
        }

        $response = parent::send($request);

        $contentType = 'Content-Type: application/octet-stream';
        if ($response->getHeaders()->has('Content-Type')) {
            $header = $response->getHeaders()->get('Content-Type');
            $contentType = $header instanceof HeaderInterface ? $header->toString() : 'UnknownContentType';
        }

        if ($response->getStatusCode() >= 500) {
            $this->logger->warn(
                sprintf(
                    self::RESPONSE_LOG_MESSAGE,
                    $contentType,
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    strlen($response->getContent())
                ),
                [
                    'category' => 'HTTP_CLIENT',
                    'sub_request_time' => microtime(true) - $preRequest,
                    'response' => $response->getBody()
                ]
            );
        } else {
            $this->logger->info(
                sprintf(
                    self::RESPONSE_LOG_MESSAGE,
                    $contentType,
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    strlen($response->getContent())
                ),
                ['category' => 'HTTP_CLIENT', 'sub_request_time' => microtime(true) - $preRequest]
            );
        }

        return $response;
    }
}
