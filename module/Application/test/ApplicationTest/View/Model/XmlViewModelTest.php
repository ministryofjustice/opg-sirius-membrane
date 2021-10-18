<?php
namespace Application\test\View\Model;

use PHPUnit\Framework\TestCase;
use Application\View\Model\XmlModel;

class XmlModelTest extends TestCase
{
    public function testSerializingOfOneDimensionalArray()
    {
        $variables = [
            'test' => 'value1',
            'test2' => 'value2',
        ];
        $xmlViewModel = new XmlModel($variables);

        $xml = $xmlViewModel->serialize();

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><response>' .
            '<test>value1</test>' .
            '<test2>value2</test2>' .
            '</response>',
            $xml
        );
    }

    public function testSerializingOfTwoDimensionalArray()
    {
        $variables = [
            'test' => ['test3' => 'value2'],
            'test2' => 'value2',
        ];
        $xmlViewModel = new XmlModel($variables);

        $xml = $xmlViewModel->serialize();

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><response>' .
            '<test><test3>value2</test3></test>' .
            '<test2>value2</test2>' .
            '</response>',
            $xml
        );
    }

    public function testSerializingOfArrayWithNullValues()
    {
        $variables = [
            'test' => ['test3' => 'value2'],
            'test2' => null,
        ];
        $xmlViewModel = new XmlModel($variables);

        $xml = $xmlViewModel->serialize();

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><response>' .
            '<test><test3>value2</test3></test>' .
            '<test2 />' .
            '</response>',
            $xml
        );
    }
}
