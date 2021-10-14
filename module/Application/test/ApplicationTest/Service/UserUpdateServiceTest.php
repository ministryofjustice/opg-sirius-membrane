<?php

namespace ApplicationTest\Service;

use Application\Model\Entity\UserAccount;
use Application\Service\SecurityLogger;
use Application\Service\UserUpdateService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;

class UserUpdateServiceTest extends TestCase
{
    protected $mockEntityManager;
    protected $mockUserAccount;
    protected $mockUserAccountRepository;

    private $securityLogger;
    private $userUpdateService;

    public function setup(): void
    {
        parent::setUp();

        /** @var EntityManager|MockObject */
        $this->mockEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccountRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccount = $this->createMock('Application\Model\Entity\UserAccount');

        /** @var SecurityLogger|MockObject */
        $this->securityLogger = $this->createMock(SecurityLogger::class);

        $this->userUpdateService = new UserUpdateService($this->mockEntityManager, $this->securityLogger);
    }

    public function testUserUpdateNonExistentUser()
    {
        $expectedReturn = [
            'status' => Response::STATUS_CODE_404,
            'body' => [],
        ];

        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo(43))
            ->will($this->returnValue(null));

        $updateUserReturn = $this->userUpdateService->updateUser(43, []);

        $this->assertEquals($expectedReturn, $updateUserReturn);
    }

    public function testUserUpdateUserUnlock()
    {
        $expectedReturn = [
            'status' => Response::STATUS_CODE_200,
            'body' => [],
        ];

        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo(43))
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(UserAccount::STATUS_LOCKED));

        $this->mockUserAccount->expects($this->once())
            ->method('setStatus')
            ->with($this->equalTo(UserAccount::STATUS_ACTIVE));

        $this->mockUserAccount->expects($this->once())
            ->method('resetUnsuccessfulLoginAttempts');

        $this->mockUserAccount->expects($this->once())
            ->method('setAdmin')
            ->with($this->equalTo(false));

        $updateUserReturn = $this->userUpdateService->updateUser(
            43,
            [
                'status' => UserAccount::STATUS_ACTIVE,
                'roles' => ['OPG User'],
            ]
        );

        $this->assertEquals($expectedReturn, $updateUserReturn);
    }

    public function testUserUpdateUserSuspendMakeNonAdmin()
    {
        $expectedReturn = [
            'status' => Response::STATUS_CODE_200,
            'body' => [],
        ];

        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo(43))
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(UserAccount::STATUS_ACTIVE));

        $this->mockUserAccount->expects($this->once())
            ->method('setStatus')
            ->with($this->equalTo(UserAccount::STATUS_SUSPENDED));

        $this->mockUserAccount->expects($this->once())
            ->method('setAdmin')
            ->with($this->equalTo(false));

        $this->securityLogger->expects($this->once())
            ->method('userSuspended')
            ->with(43);

        $updateUserReturn = $this->userUpdateService->updateUser(
            43,
            [
                'status' => UserAccount::STATUS_SUSPENDED,
                'roles' => ['OPG User'],
            ]
        );

        $this->assertEquals($expectedReturn, $updateUserReturn);
    }

    public function testUserUpdateUserUnsuspendMakeAdmin()
    {
        $expectedReturn = [
            'status' => Response::STATUS_CODE_200,
            'body' => [],
        ];

        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo(43))
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(UserAccount::STATUS_SUSPENDED));

        $this->mockUserAccount->expects($this->once())
            ->method('setStatus')
            ->with($this->equalTo(UserAccount::STATUS_ACTIVE));

        $this->mockUserAccount->expects($this->once())
            ->method('setAdmin')
            ->with($this->equalTo(true));

        $this->securityLogger->expects($this->once())
            ->method('userActivated')
            ->with(43);

        $updateUserReturn = $this->userUpdateService->updateUser(
            43,
            [
                'status' => UserAccount::STATUS_ACTIVE,
                'roles' => ['OPG User', 'System Admin'],
            ]
        );

        $this->assertEquals($expectedReturn, $updateUserReturn);
    }

    public function testUserDelete()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->with('Application\Model\Entity\UserAccount')
            ->willReturn($this->mockUserAccountRepository);

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(12)
            ->willReturn($this->mockUserAccount);

        $this->mockEntityManager->expects($this->once())
            ->method('remove')
            ->with($this->mockUserAccount);

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->userUpdateService->deleteUser(12);
    }
}
