<?php

namespace Application\ContentNegotiation;

use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\ApiTools\ContentNegotiation\ParameterDataContainer;

class XmlContentTypeListener
{
    public function __invoke(MvcEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        if (!$this->isXmlRequest($request)) {
            return;
        }

        $parameterData = new ParameterDataContainer();

        $bodyParams = match ($request->getMethod()) {
            $request::METHOD_POST, $request::METHOD_PATCH, $request::METHOD_PUT => $this->extractBodyParametersFromXml(
                $request->getContent()
            ),
            default => [],
        };

        $parameterData->setBodyParams($bodyParams);
        $event->setParam('LaminasContentNegotiationParameterData', $parameterData);
    }

    protected function isXmlRequest($request)
    {
        $contentType = $request->getHeader('Content-type');

        if (!$contentType || !$contentType->match('application/xml')) {
            return false;
        }

        return true;
    }

    protected function extractBodyParametersFromXml($xmlString)
    {
        $xmlObject = simplexml_load_string($xmlString);
        return json_decode(json_encode((array)$xmlObject), true);
    }
}
