<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserSessionService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Log\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class SessionRestControllerTest extends BaseControllerTestCase
{
    private $mockAuthenticationServiceConstructor;
    private $mockUserSessionService;
    private $logger;

    /**
     * @var SecurityLogger|MockObject
     */
    private $securityLogger;

    public function setup(): void
    {
        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $this->mockAuthenticationServiceConstructor = $this->createMock(AuthenticationServiceConstructor::class);
        $serviceManager->setService(AuthenticationServiceConstructor::class, $this->mockAuthenticationServiceConstructor);

        $this->mockUserSessionService = $this->getMockBuilder(UserSessionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceManager->setService(
            UserSessionService::class,
            $this->mockUserSessionService
        );

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceManager->setService(Logger::class, $this->logger);

        $this->securityLogger = $this->createMock(SecurityLogger::class);
        $serviceManager->setService(SecurityLogger::class, $this->securityLogger);
    }

    public function testCanLogInWithValidCredentials()
    {
        $serviceResponse = [
            'status' => Response::STATUS_CODE_201,
            'body' => [
                'email' => 'valid@email.com',
                'authentication_token' => 'validSessionId',
                'userId' => 2,
            ],
        ];

        // Set up mock SessionManager object to return a session ID.
        $this->mockUserSessionService->expects($this->atLeastOnce())
            ->method('openUserSession')
            ->with('valid@email.com', 'ValidPassword')
            ->will($this->returnValue($serviceResponse));

        // Set expectations in the logger mock
        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->logicalOr(
                'User login successful',
                $this->anything()
            ));

        $this->dispatchJsonLoginRequest('valid@email.com', 'ValidPassword');

        $this->assertResponseStatusCode(Response::STATUS_CODE_201);
        $content = $this->getResponse()->getContent();
        $this->assertEquals('{"email":"valid@email.com","authentication_token":"validSessionId","userId":2}', $content);
    }

    public function preauthStatuses()
    {
        return [
            [UserAccount::STATUS_ACTIVE, true],
            [UserAccount::STATUS_NOT_ACTIVATED, true],
            [UserAccount::STATUS_LOCKED, false],
            [UserAccount::STATUS_SUSPENDED, false],
        ];
    }

    /**
     * @dataProvider preauthStatuses
     */
    public function testCanLogInWithPreconfiguredCredentialsIfValidStatus(string $status, bool $isValid)
    {
        $user = new UserAccount();
        $user->setId(39);
        $user->setEmail('my-email@opgtest.com');
        $user->setStatus($status);

        $mockAuthenticationService = $this->createMock(AuthenticationService::class);
        $mockAuthenticationService->expects($this->once())->method('hasIdentity')->willReturn(true);
        $mockAuthenticationService->expects($this->atLeastOnce())->method('getIdentity')->willReturn($user);

        $this->mockAuthenticationServiceConstructor->expects($this->once())
            ->method('getBypassMembrane')
            ->willReturn($mockAuthenticationService);

        if ($isValid) {
            $this->mockUserSessionService->expects($this->atLeastOnce())
                ->method('getSessionId')
                ->willReturn('validSessionId');
        }

        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->logicalOr(
                'Preauthorized login successful',
                $this->anything()
            ));

        $headers = (new Headers())
            ->addHeaderLine('Content-Type', 'application/json')
            ->addHeaderLine('Accept', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $request->setContent('{"preauthorized":true}');
        $this->dispatch('/auth/v1/sessions');

        if ($isValid) {
            $this->assertResponseStatusCode(Response::STATUS_CODE_201);
            $content = $this->getResponse()->getContent();
            $this->assertEquals('{"email":"my-email@opgtest.com","authentication_token":"validSessionId"}', $content);
        } else {
            $this->assertResponseStatusCode(Response::STATUS_CODE_403);
            $content = $this->getResponse()->getContent();
            $this->assertEquals('{"status":"' . $status . '"}', $content);
        }
    }

    public function testCanNotLogInWithInvalidEmail()
    {
        $serviceResponse = [
            'status' => Response::STATUS_CODE_401,
            'body' => [
                'error' => 'Invalid email or password.',
            ],
        ];

        // Set up mock SessionManager object to return a session ID.
        $this->mockUserSessionService->expects($this->once())
            ->method('openUserSession')
            ->with('invalid@email.com', 'ValidPassword')
            ->will($this->returnValue($serviceResponse));

        // Set expectations in the logger mock
        $this->securityLogger->expects($this->once())
            ->method('loginFailed')
            ->with('Invalid email or password.', null);

        $this->dispatchJsonLoginRequest('invalid@email.com', 'ValidPassword');
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testCanNotLogInWithInvalidPassword()
    {
        $serviceResponse = [
            'status' => Response::STATUS_CODE_401,
            'body' => [
                'error' => 'Invalid email or password.',
                'userId' => 123,
            ],
        ];

        // Set up mock SessionManager object to return a session ID.
        $this->mockUserSessionService->expects($this->once())
            ->method('openUserSession')
            ->with('invalid@email.com', 'ValidPassword')
            ->will($this->returnValue($serviceResponse));

        // Set expectations in the logger mock
        $this->securityLogger->expects($this->once())
            ->method('loginFailed')
            ->with('Invalid email or password.', 123);

        $this->dispatchJsonLoginRequest('invalid@email.com', 'ValidPassword');
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testCanDeleteSession()
    {
        $serviceResponse = [
            'status' => Response::STATUS_CODE_204,
            'body' => [
                'deleted' => 'Session deleted',
            ],
        ];

        // Set up mock SessionManager object to return a session ID.
        $this->mockUserSessionService->expects($this->once())
            ->method('closeUserSession')
            ->with('validSessionId')
            ->will($this->returnValue($serviceResponse));

        // Set expectations in the logger mock
        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->logicalOr(
                'User logout successful',
                $this->anything()
            ));

        $this->dispatchLogoutRequest('validSessionId');
        $this->assertResponseStatusCode(Response::STATUS_CODE_204);
    }

    protected function dispatchJsonLoginRequest($username, $password)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $loginJson = '{"user":{"email":"' . $username . '","password":"' . $password . '"}}';
        $request->setContent($loginJson);

        $this->dispatch('/auth/sessions');
    }

    protected function dispatchLogoutRequest($admin_token)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(json_encode([]));
        $request->setMethod('DELETE');

        $this->dispatch('/auth/sessions/' . $admin_token);
    }
}
