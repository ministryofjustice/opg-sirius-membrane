<?php

declare(strict_types=1);

namespace Application\Proxy;

use Application\Service\RequestService;
use JwtLaminasAuth\Authentication\Storage\Header;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;

/**
 * Class ApplicationProxy
 *
 */
class ApplicationProxy
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Headers
     */
    protected $existingHeaders;

    /**
     * @var Headers
     */
    protected $newHeaders;

    /**
     * @param array $config
     * @param HttpClient $httpClient
     */
    public function __construct(array $config, HttpClient $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function send(Request $request)
    {
        $forwardRequest = $this->createForwardRequestFromRequest($request);
        $response = $this->httpClient->send($forwardRequest);

        return $response;
    }

    protected function createForwardRequestFromRequest($request)
    {
        $forwardRequest = new Request();

        $uri = $this->buildForwardUri($request);
        $forwardRequest->setUri($uri);

        $method = $this->resolveForwardMethod($request);
        $forwardRequest->setMethod($method);

        $this->copyRequestParameters($forwardRequest, $request);

        $this->existingHeaders = $request->getHeaders();
        $this->newHeaders = new Headers();
        $this->copyHeaders();

        $forwardRequest->setContent($request->getContent());
        $forwardRequest->setHeaders($this->newHeaders);

        return $forwardRequest;
    }

    protected function buildForwardUri($request)
    {
        $uri = $this->config['baseUri'] . str_replace('/auth', '', $request->getUri()->getPath() ?? '');

        return $uri;
    }

    protected function copyRequestParameters($forwardRequest, $request)
    {
        if ($request->getMethod() == Request::METHOD_GET) {
            $forwardRequest->setQuery($request->getQuery());
        } else {
            $forwardRequest->setPost($request->getPost());
        }
    }

    protected function copyHeaders()
    {
        $this->copyHeader('Content-Type');
        $this->copyHeader('User-Agent');
        $this->copyHeader('Accept-Language');
        $this->copyHeader('Accept-Encoding');
        $this->copyHeader('Accept');
        $this->copyHeader('X-REQUEST-ID');
        $this->copyHeader(RequestService::HEADER_USER_ID);
        $this->copyHeader(RequestService::HEADER_USER_ROLES);
        $this->copyHeader(Header::HEADER_NAME);
    }

    /**
     * The caller may specify a query string like forwardMethodOverride=DELETE
     * This will cause us to forward the request using the given method rather
     * than copying the same method as the caller.  We ran into issues that
     * ran deep into the Framework?/PHP?/Curl? regarding sending content bodies
     * in DELETE requests resulting in Apache hanging.
     */
    protected function resolveForwardMethod($request)
    {
        $query = $request->getQuery();
        $forwardMethod = $query->get('forwardMethodOverride');
        if ($forwardMethod != null) {
            $method = strtoupper($forwardMethod);
        } else {
            $method = $request->getMethod();
        }

        return $method;
    }

    /**
     * Copy header of the given name from the incoming request
     *
     * @param string $name
     */
    protected function copyHeader($name)
    {
        $existingHeader = $this->existingHeaders->get($name);

        if ($existingHeader instanceof HeaderInterface) {
            $this->newHeaders->addHeaderLine(
                $name,
                $existingHeader->getFieldValue()
            );
        }
    }
}
