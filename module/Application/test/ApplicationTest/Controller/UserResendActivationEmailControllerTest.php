<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\UserCreationService;
use Application\Service\UserService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Headers;

class UserResendActivationEmailControllerTest extends BaseControllerTestCase
{
    protected $mockAuthenticationService;
    protected $mockUserCreationService;
    protected $mockUserAccount;
    protected $mockUserService;
    protected $mockAuthenticationServiceConstructor;

    public function setup(): void
    {
        parent::setUp();

        // Set up mock replacement services so they can be manipulated for testing.
        $this->mockAuthenticationService = $this->createMock(AuthenticationService::class);
        $this->mockUserCreationService = $this->getMockBuilder(UserCreationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUserService = $this->getMockBuilder(UserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUserAccount = $this->createMock(UserAccount::class);

        $this->mockAuthenticationServiceConstructor = $this->createMock(AuthenticationServiceConstructor::class);
        $this->mockAuthenticationServiceConstructor->method('getNormal')->willReturn($this->mockAuthenticationService);
        $this->mockAuthenticationServiceConstructor->method('getBypassMembrane')->willReturn($this->mockAuthenticationService);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(AuthenticationServiceConstructor::class, $this->mockAuthenticationServiceConstructor);
        $serviceManager->setService(UserService::class, $this->mockUserService);
        $serviceManager->setService(UserCreationService::class, $this->mockUserCreationService);
    }

    public function testResendActivationEmailTriggersResendingOfEmail()
    {
        $newUser = new UserAccount();
        $newUser->setEmail('email@example.com');
        $newUser->setId(999);

        // Set up mock User Account to be an admin.
        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserCreationService->expects($this->once())
            ->method('resendActivationEmail')
            ->with(999);

        $this->dispatchJsonResendActivationEmailRequest(999);
    }

    protected function dispatchJsonResendActivationEmailRequest($uid)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(json_encode([]));
        $request->setMethod('POST');

        $this->dispatch('/auth/users/' . $uid . '/activation-request');
    }
}
