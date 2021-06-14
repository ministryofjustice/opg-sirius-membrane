<?php

namespace Application\test\View\Strategy;

use Application\View\Renderer\XmlRenderer;
use Application\View\Strategy\XmlStrategy;
use Laminas\View\ViewEvent;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Response;

class XmlStrategyTest extends TestCase
{
    protected $xmlStrategy;
    protected $response;
    protected $viewEvent;

    public function setup(): void
    {
        $xmlRenderer = $this->createMock(XmlRenderer::class);
        $this->viewEvent = $this->createMock(ViewEvent::class);

        $this->response = new Response();
        $this->xmlStrategy = new XmlStrategy($xmlRenderer);

        $this->viewEvent->expects($this->any())
            ->method('getRenderer')
            ->will($this->returnValue($xmlRenderer));

        $this->viewEvent->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($this->response));

        $this->viewEvent->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue('<xml>test</xml>'));
    }

    public function testXmlStrategyAddsXmlHeaderToResponse()
    {
        $this->xmlStrategy->injectResponse($this->viewEvent);
        $headers = $this->response->getHeaders();
        $contentTypeHeader = $headers->get('Content-Type');

        $this->assertEquals('Content-Type: application/xml; charset=utf-8', $contentTypeHeader->toString());
    }

    public function testXmlStrategyAddsXmlContentToResponse()
    {
        $this->xmlStrategy->injectResponse($this->viewEvent);

        $this->assertEquals('<xml>test</xml>', $this->response->getContent());
    }
}
