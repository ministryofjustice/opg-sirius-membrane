<?php

namespace Application\test\ContentNegotiation;

use Application\ContentNegotiation\XmlContentTypeListener;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request;

class XmlContentTypeListenerTest extends TestCase
{
    protected $mockMvcEvent;
    protected $parameterData;

    public function setup(): void
    {
        $this->mockMvcEvent = $this->createMock(MvcEvent::class);
        $this->mockMvcEvent->expects($this->any())
            ->method('setParam')
            ->will($this->returnCallback(function ($key, $parameterData) {
                $this->parameterData = $parameterData;
            }));
    }

    public function testXmlContentIsDeserialisedWhenXmlContentTypeHeaderIsSet()
    {
        $this->setRequest(
            Request::METHOD_POST,
            ['Content-Type: application/xml'],
            $this->getXmlRequestBody()
        );

        $xmlContentTypeListener = new XmlContentTypeListener();
        $xmlContentTypeListener($this->mockMvcEvent);
        $result = $this->parameterData->getBodyParam('user');

        $this->assertEquals('casrec@opgtest.com', $result['email']);
    }

    public function testBodyParamsAreEmptyOnGetRequests()
    {
        $this->setRequest(
            Request::METHOD_GET,
            ['Content-Type: application/xml'],
            $this->getXmlRequestBody()
        );

        $xmlContentTypeListener = new XmlContentTypeListener();
        $xmlContentTypeListener($this->mockMvcEvent);
        $result = $this->parameterData->getBodyParam('user');

        $this->assertEmpty($this->parameterData->getBodyParams());
    }

    public function testNothingHappensIfContentTypeIsJson()
    {
        $this->setRequest(
            Request::METHOD_POST,
            ['Content-Type: application/json'],
            '{"user" : { "email" : "casrec@opgtest.com"}}'
        );

        $xmlContentTypeListener = new XmlContentTypeListener();
        $xmlContentTypeListener($this->mockMvcEvent);

        $this->assertEmpty($this->parameterData);
    }

    private function setRequest($method, $headersArray, $body = null)
    {
        $headers = new Headers();
        foreach ($headersArray as $header) {
            $headers->addHeaderLine($header);
        }
        $request = new Request();
        $request->setMethod($method);
        $request->setHeaders($headers);

        if ($body) {
            $request->setContent($body);
        }

        $this->mockMvcEvent->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        return $request;
    }

    protected function getXmlRequestBody()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns="opg:sirius">'
            . '<user>'
            . '<email>casrec@opgtest.com</email>'
            . '<password>Password1</password>'
            . '</user>'
            . '</request>';

        return $xml;
    }
}
