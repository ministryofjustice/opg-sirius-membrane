<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Model\Entity\UserAccount;
use Application\Service\UserService;
use Laminas\Http\Headers;
use Laminas\Http\Response;

class UserStatusControllerTest extends BaseControllerTestCase
{
    protected $mockAuthenticationService;
    protected $mockUserCreationService;
    protected $mockUserAccount;
    protected $mockUserService;

    public function setup(): void
    {
        parent::setUp();

        $this->mockUserService = $this->getMockBuilder(UserService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(UserService::class, $this->mockUserService);
    }

    public function testSuccessfulRequest()
    {
        $testUserEntity = new UserAccount();
        $testUserEntity->setStatus('active');

        $this->mockUserService->expects($this->once())
            ->method('retrieveUserAccountEntity')
            ->with('3')
            ->will($this->returnValue($testUserEntity));

        $this->dispatchStatusRequestWithId(3);

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testNotFoundRequest()
    {
        $this->mockUserService->expects($this->once())
            ->method('retrieveUserAccountEntity')
            ->with('56')
            ->will($this->returnValue(null));

        $this->dispatchStatusRequestWithId(56);

        $this->assertResponseStatusCode(Response::STATUS_CODE_404);
    }

    protected function dispatchStatusRequestWithId($userId)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(json_encode([]));
        $request->setMethod('GET');

        $this->dispatch("/auth/users/$userId/status");
    }
}
