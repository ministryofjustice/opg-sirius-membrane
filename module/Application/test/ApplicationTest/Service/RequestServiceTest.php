<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\RequestService;
use Exception;
use JwtLaminasAuth\Authentication\Storage\Header;
use JwtLaminasAuth\Service\JwtService;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Testcase;

/**
 */
class RequestServiceTest extends Testcase
{
    /**
     * @var RequestService
     */
    private $service;

    public function setup(): void
    {
        $mockToken = $this->createMock(Token::class);
        $mockToken->expects($this->any())
            ->method('toString')
            ->willReturn('Token');

        /** @var JwtService|MockObject $mockJwt */
        $mockJwt = $this->createMock(JwtService::class);
        $mockJwt->method('createSignedToken')->with($this->anything())->willReturn($mockToken);
        $this->service = new RequestService($mockJwt);
        parent::setUp();
    }

    public function testAreInvalidHeadersPresentReturnsTrueWhenUserIdHeaderPresent()
    {
        $headers = new Headers();
        $headers->addHeaderLine('X-User-Id', 'test@example.com');

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->assertTrue($this->service->areInvalidHeadersPresent());
    }

    public function testAreInvalidHeadersPresentReturnsTrueWhenUserRolesHeaderPresent()
    {
        $headers = new Headers();
        $headers->addHeaderLine('X-User-Roles', 'role-1,role-2,role-3');

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->assertTrue($this->service->areInvalidHeadersPresent());
    }

    public function testAreInvalidHeadersPresentReturnsFalseWhenNoInvalidHeadersPresent()
    {
        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->assertFalse($this->service->areInvalidHeadersPresent());
    }

    public function testAreInvalidHeadersPresentThrowsExceptionWhenRequestNotSet()
    {
        $expected = 'Request object not set';
        $actual = '';

        try {
            $this->assertFalse($this->service->areInvalidHeadersPresent());
        } catch (Exception $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testRecognisesWhenAuthenticationRequest()
    {
        $request = new Request();
        $request->setUri('http://www.example.com/auth/anything');
        $this->service->setRequest($request);

        $this->assertTrue($this->service->isDeviseRequest());
    }

    public function testRecognitionOfLoginRequestWithLoginInUrl()
    {
        $request = new Request();
        $request->setUri('http://www.example.com/auth/login');
        $this->service->setRequest($request);

        $this->assertTrue(
            $this->service->isLoginRequest(),
            'Failed to recognise that auth/login is login request'
        );
    }

    public function testRecognitionOfLoginRequestWithSessionInUrl()
    {
        $request = new Request();
        $request->setUri('http://www.example.com/auth/session');
        $this->service->setRequest($request);

        $this->assertTrue(
            $this->service->isLoginRequest(),
            'Failed to recognise that auth/session is login request'
        );
    }

    public function testRecognisesWhenNotAuthenticationRequest()
    {
        $request = new Request();
        $request->setUri('http://www.example.com/api/awesome/stuff');
        $this->service->setRequest($request);

        $this->assertFalse($this->service->isDeviseRequest());
    }

    public function testRecognisesWhenNotAuthenticationRequestButAuthInPath()
    {
        $request = new Request();
        $request->setUri('http://www.example.com/api/auth/login');
        $this->service->setRequest($request);

        $this->assertFalse($this->service->isDeviseRequest());
    }

    public function testCanGetSecureTokenFromHeader()
    {
        $expected = md5('test');

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine('Http-Secure-Token', $expected);

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->assertEquals(
            $expected,
            $this->service->getSecureToken()
        );
    }

    public function testReturnsNullWhenSecureTokenNotFound()
    {
        $expected = null;

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->assertEquals(
            $expected,
            $this->service->getSecureToken()
        );
    }

    public function testUpdateHeadersWithUserId()
    {
        $userId = 'brian.maiden@example.com';

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine(RequestService::HEADER_SECURE_TOKEN, md5('test'));

        $request = new Request();
        $request->setHeaders($headers);

        $this->service->setRequest($request);

        $this->service->updateHeadersWithUserId($userId);

        $newHeaders = $this->service->getRequest()->getHeaders();

        $this->assertEquals(
            $userId,
            $newHeaders->get(RequestService::HEADER_USER_ID)->getFieldValue()
        );

        $this->assertEquals(
            'Bearer Token',
            $newHeaders->get(Header::HEADER_NAME)->getFieldValue()
        );

        $this->assertEquals(
            null,
            $newHeaders->get(RequestService::HEADER_SECURE_TOKEN)
        );
    }
}
