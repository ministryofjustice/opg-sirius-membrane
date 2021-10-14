<?php

namespace Application\View\Model;

use Laminas\View\Model\ViewModel;

class XmlModel extends ViewModel
{
    protected $captureTo = null;
    protected $terminate = true;

    /**
     * Returns the XML representation of the variables returned by the controller.
     *
     * @return string
     */
    public function serialize()
    {
        $variables = $this->getVariables();

        $xml = $this->arrayToXml($variables);

        return $this->wrapXmlInResponse($xml);
    }

    //@todo Refactor to use SimpleXml
    protected function arrayToXml($array)
    {
        $xml = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $xml .= $this->makeElement($key, $this->arrayToXml($value));
            } else {
                $xml .= $this->makeElement($key, $value);
            }
        }

        return $xml;
    }

    protected function makeElement($element, $value)
    {
        if ($value) {
            $xmlElement = '<' . $element . '>' . $value . '</' . $element . '>';
        } else {
            $xmlElement = '<' . $element . ' />';
        }

        return $xmlElement;
    }

    protected function wrapXmlInResponse($xmlData)
    {
        return '<?xml version="1.0" encoding="UTF-8"?><response>' .
            $xmlData .
        '</response>';
    }
}
