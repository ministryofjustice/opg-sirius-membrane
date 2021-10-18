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

        $bodyParams = [];
        switch ($request->getMethod()) {
            case $request::METHOD_POST:
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
                $content = $request->getContent();
                $bodyParams = $this->extractBodyParametersFromXml($content);

                break;
        }

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
        $array = json_decode(json_encode((array)$xmlObject), true);

        return $array;
    }
}
