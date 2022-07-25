<?php

namespace ApplicationTest\Controller;

use Application\Proxy\ApplicationProxy;
use Application\Service\RequestService;
use Laminas\Http\Client;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Stdlib\Parameters;

/**
 * Class ApplicationProxyTest
 *
 */
class ApplicationProxyTest extends TestCase
{
    protected $requestHeadersToCopy = [
            ['Content-Type', 'application/json'],
            ['User-Agent', 'firefox'],
            ['Accept-Language', 'english'],
            ['Accept-Encoding', 'UTF-8'],
            ['Accept', '*'],
            [RequestService::HEADER_USER_ROLES, '*'],
            [RequestService::HEADER_USER_ID, 'userID'],
        ];

    protected $requestHeadersNotToCopy = [
        ['FakeHeader', 'do not copy'],
        ['SecondFake', 'do not copy'],
    ];

    protected $requestToProxy;
    protected $copiedRequest;
    protected $config;

    public function setup(): void
    {
        $this->requestToProxy = new Request();
        $this->copiedRequest = null;
        $this->config = ['baseUri' => 'http://backend.com'];
    }

    /**
     * @dataProvider requestHeadersToCopyProvider
     */
    public function testSpecifiedHeadersAreCopiedIntoForwardedRequest($header, $value)
    {
        $requestHeaders = $this->getRequestHeadersForRequestToProxy();
        $this->requestToProxy->setHeaders($requestHeaders);

        $this->sendRequest();

        $header = $this->copiedRequest->getHeader($header);
        $this->assertEquals($value, $header->getFieldValue());
    }

    /**
     * @dataProvider requestHeadersNotToCopyProvider
     */
    public function testUnspecifiedRequestHeadersAreNotCopiedIntoForwardRequest($header)
    {
        $requestHeaders = $this->getRequestHeadersForRequestToProxy();
        $this->requestToProxy->setHeaders($requestHeaders);

        $this->sendRequest();

        $header = $this->copiedRequest->getHeader($header);
        $this->assertFalse($header);
    }

    public function testRequestContentIsCopiedIntoForwardRequest()
    {
        $this->requestToProxy->setContent('test content');

        $this->sendRequest();

        $this->assertEquals('test content', $this->copiedRequest->getContent());
    }

    public function testRequestForwardMethodQueryStringIsHonoured()
    {
        $parameters = new Parameters();
        $parameters->fromString('forwardMethodOverride=post');
        $this->requestToProxy->setMethod('PUT');
        $this->requestToProxy->setQuery($parameters);

        $this->sendRequest();

        $this->assertEquals('POST', $this->copiedRequest->getMethod());
    }

    public function testCopiedRequestHasSameMethodAsProxiedRequest()
    {
        $parameters = new Parameters();
        $parameters->fromString('forwardMethodOverride=post');
        $this->requestToProxy->setMethod('PUT');
        $this->requestToProxy->setQuery($parameters);

        $this->sendRequest();

        $this->assertEquals('POST', $this->copiedRequest->getMethod());
    }

    public function testCopiedRequestHasTheCorrectUriToForwardTheRequest()
    {
        $this->requestToProxy->setUri('http://www.frontendofsite.com/blah/blah');

        $this->sendRequest();

        $this->assertEquals('http://backend.com/blah/blah', $this->copiedRequest->getUri());
    }

    public function testCopiedRequestHasAuthRemovedFromUri()
    {
        $this->requestToProxy->setUri('http://www.frontendofsite.com/auth/blah/blah');

        $this->sendRequest();

        $this->assertEquals('http://backend.com/blah/blah', $this->copiedRequest->getUri());
    }

    public function testCopiedRequestContainsAllPostValuesSetInProxiedRequest()
    {
        $parameters = new Parameters(['testpost' => 'value']);
        $this->requestToProxy->setMethod('POST');
        $this->requestToProxy->setPost($parameters);

        $this->sendRequest();

        $this->assertEquals('value', $this->copiedRequest->getPost('testpost'));
    }

    public function testCopiedRequestContainsAllGetValuesSetInProxiedRequest()
    {
        $parameters = new Parameters(['testget' => 'value']);
        $this->requestToProxy->setMethod('GET');
        $this->requestToProxy->setQuery($parameters);

        $this->sendRequest();

        $this->assertEquals('value', $this->copiedRequest->getQuery('testget'));
    }

    protected function sendRequest()
    {
        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($copiedRequest) {
                $this->copiedRequest = $copiedRequest;
            }));

        $proxy = new ApplicationProxy($this->config, $mockHttpClient);
        $proxy->send($this->requestToProxy);
    }

    protected function getRequestHeadersForRequestToProxy()
    {
        $requestHeaders = new Headers();
        foreach ($this->getAllRequestHeaders() as list($headerName, $headerValue)) {
            $requestHeaders->addHeaderLine($headerName . ': ' . $headerValue);
        }

        return $requestHeaders;
    }

    protected function getAllRequestHeaders()
    {
        return $this->requestHeadersToCopy + $this->requestHeadersNotToCopy;
    }

    public function requestHeadersToCopyProvider()
    {
        return $this->requestHeadersToCopy;
    }

    public function requestHeadersNotToCopyProvider()
    {
        return $this->requestHeadersNotToCopy;
    }
}
