<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Laminas\Http\Headers;

class ContentNegotiationTest extends BaseControllerTestCase
{
    /**
     * @group functional
     */
    public function testContentIsReturnedInJsonWhenSettingAJsonAcceptTypeInRequestHeader()
    {
        $this->dispatchJsonLoginRequest();

        $content = $this->getResponse()->getContent();
        $this->assertTrue($this->isValidJson($content), 'Response is not valid json');
    }

    /**
     * @group functional
     */
    public function testJsonContentTypeHeaderIsSetWhenSettingAJsonAcceptTypeInRequestHeader()
    {
        $this->dispatchJsonLoginRequest();

        $headers = $this->getResponse()->getHeaders();
        $contentTypeHeader = $headers->get('Content-Type');

        $this->assertEquals('application/json', $contentTypeHeader->getMediaType());
    }

    /**
     * @group data-dependent
     * @group functional
     * @runInSeparateProcess
     */
    public function testUserCanLoginUsingJsonContentBody()
    {
        $this->dispatchJsonLoginRequest();
        $jsonResponse = json_decode($this->getResponse()->getContent(), true);
        $this->getResponse()->getContent();

        $this->assertArrayHasKey('authentication_token', $jsonResponse);
        $this->assertNotEmpty($jsonResponse['authentication_token']);
    }

    /**
     * @group functional
     */
    public function testContentIsReturnedInXmlWhenSettingAnXMLAcceptTypeInRequestHeader()
    {
        $this->dispatchXmlLoginRequest();
        $xmlResponse = $this->getResponse()->getContent();

        $this->assertTrue($this->isValidXml($xmlResponse), 'Response is not valid xml');
    }

    /**
     * @group functional
     */
    public function testXmlContentTypeHeaderIsSetWhenSettingAnXMLAcceptTypeInRequestHeader()
    {
        $this->dispatchXmlLoginRequest();

        $headers = $this->getResponse()->getHeaders();
        $contentTypeHeader = $headers->get('Content-Type');

        $this->assertEquals('application/xml', $contentTypeHeader->getMediaType());
    }

    /**
     * @group data-dependent
     * @group functional
     * @runInSeparateProcess
     */
    public function testUserCanLoginUsingXmlContentBody()
    {
        $this->dispatchXmlLoginRequest();
        $xmlContent = $this->getResponse()->getContent();
        $xmlObject = simplexml_load_string($xmlContent);
        $array = json_decode(json_encode((array)$xmlObject), true);

        $this->assertArrayHasKey('authentication_token', $array);
        $this->assertNotEmpty($array['authentication_token']);
    }

    protected function getLoginXml()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns="opg:sirius">'
            . '<user>'
            . '<email>manager@opgtest.com</email>'
            . '<password>Password1</password>'
            . '</user>'
            . '</request>';

        return $xml;
    }

    protected function isValidXml($content)
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        libxml_clear_errors();

        return $doc !== false;
    }

    protected function isValidJson($content)
    {
        $json = json_decode($content, true);

        return $json !== false;
    }

    protected function dispatchJsonLoginRequest()
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $loginJson = '{"user":{"email":"manager@opgtest.com","password":"Password1"}}';
        $request->setContent($loginJson);

        $this->dispatch('/auth/sessions');
    }

    protected function dispatchXmlLoginRequest()
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/xml');
        $headers->addHeaderLine('Content-Type', 'application/xml');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $loginXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<request xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="opg:sirius">'
            . '<user>'
            . '<email>manager@opgtest.com</email>'
            . '<password>Password1</password>'
            . '</user>'
            . '</request>';
        $request->setContent($loginXml);

        $this->dispatch('/auth/sessions');
    }
}
