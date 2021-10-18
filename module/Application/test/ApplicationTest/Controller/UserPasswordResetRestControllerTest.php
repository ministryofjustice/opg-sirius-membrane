<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Service\SecurityLogger;
use Application\Service\UserPasswordResetService;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Log\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class UserPasswordResetRestControllerTest extends BaseControllerTestCase
{
    protected $mockAuthenticationService;
    protected $mockUserCreationService;
    protected $mockUserAccount;

    /**
     * @var SecurityLogger|MockObject
     */
    private $securityLogger;
    private $mockUserPasswordResetService;

    public function setup(): void
    {
        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $this->mockUserPasswordResetService = $this->getMockBuilder(UserPasswordResetService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceManager->setService(
            UserPasswordResetService::class,
            $this->mockUserPasswordResetService
        );

        $this->securityLogger = $this->createMock(SecurityLogger::class);
        $serviceManager->setService(SecurityLogger::class, $this->securityLogger);
    }

    public function testItShouldCreatePasswordResetReturn201ResponseAndLogSecurityInfoWhenValidCredentialsAreProvided()
    {
        $this->mockUserPasswordResetService->expects($this->once())
            ->method('sendPasswordResetViaEmail')
            ->with('3')
            ->will($this->returnValue([]));

        // Set expectations in the mock
        $this->securityLogger->expects($this->atLeastOnce())
            ->method('passwordResetSuccessful')
            ->with($this->anything());

        $this->dispatchJsonPasswordResetCreationRequest(3);

        $this->assertResponseStatusCode(Response::STATUS_CODE_201);
    }

    public function testItShouldReturnA404ResponseAndLogSecurityErrorWhenProvidingAUserThatDoesNotExist()
    {
        $this->mockUserPasswordResetService->expects($this->once())
            ->method('sendPasswordResetViaEmail')
            ->with('56')
            ->will($this->returnValue([
                'errors' => [
                    'user' => 'User does not exist',
                ],
            ]));

        // Set expectations in the mock
        $this->securityLogger->expects($this->once())
            ->method('passwordResetFailed')
            ->with(56);

        $this->dispatchJsonPasswordResetCreationRequest(56);

        $this->assertResponseStatusCode(Response::STATUS_CODE_404);
    }

    protected function dispatchJsonPasswordResetCreationRequest($userId)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $payload = [];

        $request->setContent(json_encode($payload));

        $this->dispatch("/auth/users/$userId/password-reset");
    }
}
