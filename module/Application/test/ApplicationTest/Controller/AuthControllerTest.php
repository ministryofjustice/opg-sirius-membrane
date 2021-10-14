<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AuthController;
use Application\Service\SecurityLogger;
use Laminas\Log\Logger;
use Laminas\Http\Headers;
use PHPUnit\Framework\MockObject\MockObject;

class AuthControllerTest extends BaseControllerTestCase
{
    private $logger;

    /**
     * @var SecurityLogger|MockObject
     */
    private $securityLogger;

    public function setup(): void
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityLogger = $this->createMock(SecurityLogger::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(Logger::class, $this->logger);
        $serviceManager->setService(SecurityLogger::class, $this->securityLogger);
    }

    /**
     * @group functional
     */
    public function testCanAccessCatchAllRouteViaAuthenticationUrl()
    {
        $this->dispatch('/auth/login');

        $this->assertModuleName('Application');
        $this->assertControllerName(AuthController::class);
        $this->assertControllerClass('AuthController');
        $this->assertMatchedRouteName('authentication-proxy');
    }

    /**
     * @group functional
     */
    public function testCanAccessCatchAllRouteViaNonAuthenticationUrl()
    {
        $this->dispatch('/api/case/123');

        $this->assertModuleName('Application');
        $this->assertControllerName(AuthController::class);
        $this->assertControllerClass('AuthController');
        $this->assertMatchedRouteName('authentication-proxy');
    }

    /**
     * @group functional
     */
    public function testItShouldReturn401CodeAndLogSecurityErrorWhenUserIdHeaderIsProvided()
    {
        // Set expectations in the mock
        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'Request headers invalid',
                $this->anything()
            );

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine('X-User-Id', 'test@example.com');

        $this->getRequest()->setHeaders($headers);
        $this->dispatch('/api/awesome/stuff/123');
        $this->assertResponseStatusCode(401);
    }

    /**
     * @group functional
     */
    public function testItShouldReturn401CodeAndLogSecurityErrorWhenUserRoleHeaderIsProvided()
    {
        // Set expectations in the mock
        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'Request headers invalid',
                $this->anything()
            );

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine('X-User-Roles', 'role-1,role-2,role-3');

        $this->getRequest()->setHeaders($headers);
        $this->dispatch('/api/awesome/stuff/123');
        $this->assertResponseStatusCode(401);
    }

    /**
     * @group functional
     */
    public function testItShouldReturn401CodeAndLogSecurityErrorWhenUserIdAndUserRolesHeadersAreProvided()
    {
        // Set expectations in the mock
        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'Request headers invalid',
                $this->anything()
            );

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine('X-User-Id', 'test@example.com');
        $headers->addHeaderLine('X-User-Roles', 'role-1,role-2,role-3');

        $this->getRequest()->setHeaders($headers);
        $this->dispatch('/api/awesome/stuff/123');
        $this->assertResponseStatusCode(401);
    }

    /**
     * @group functional
     */
    public function testItShouldReturn401CodeAndLogSecurityErrorWhenUserIsNotAuthenticated()
    {
        $this->securityLogger->expects($this->once())->method('authenticationFailed');

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $this->getRequest()->setHeaders($headers);

        $this->dispatch('/api/awesome/stuff/123');
        $this->assertResponseStatusCode(401);
    }

    public function testUserReceives401WhenLoginFails()
    {
        $invalidLoginJson = '{"user":{"email":"manager@opgtest.com","password":"invalid password"}}';

        $this->postJsonLogin($invalidLoginJson);

        $this->assertResponseStatusCode(401);
    }

    /**
     * @group data-dependent
     * @group functional
     * @runInSeparateProcess
     */
    public function testUsersRequestIsSuccessfullyProxiedWhenLoggedin()
    {
        $email = 'manager@opgtest.com';
        $loginJson = '{"user":{"email":"' . $email . '","password":"Password1"}}';

        $this->postJsonLogin($loginJson);
        $this->assertResponseStatusCode(201);

        $jsonResponse = json_decode($this->getResponse()->getContent(), true);

        $authToken = $jsonResponse['authentication_token'];

        $this->assertNotEmpty($authToken);

        // reset the application so we can dispatch a new request and process a new response
        $this->reset(true);

        $headers = new Headers();
        $headers->addHeaderLine('HTTP-SECURE-TOKEN', $authToken);

        $this->getRequest()->setHeaders($headers);

        $this->dispatch('/api/v1/users/current');

        $this->assertResponseStatusCode(200);
    }

    private function postJsonLogin($loginJson)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $request->setContent($loginJson);

        $this->dispatch('/auth/sessions');
    }
}
