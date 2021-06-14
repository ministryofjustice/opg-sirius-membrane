<?php

namespace ApplicationTest\Service;

use Application\Model\Entity\UserAccount;
use Application\Service\UserService;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Response;

class UserServiceTest extends TestCase
{
    protected $mockEntityManager;

    protected $mockUserAccountRepository;

    protected $mockUserAccount;

    protected $mockQueryBuilder;

    protected $mockQuery;

    protected $userServiceConfig;

    public function setup(): void
    {
        parent::setUp();

        $this->mockEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccountRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccount = $this->createMock('Application\Model\Entity\UserAccount');

        $this->mockQueryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockQuery = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['setParameter', 'getResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userServiceConfig = [
            'one_time_password_set_lifetime' => 'P1D',
        ];
    }

    public function testGetAllUser()
    {
        $this->mockUserAccountRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->mockQueryBuilder));

        $this->mockQueryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->mockQuery));

        $this->mockUserAccount->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserAccount->expects($this->exactly(2))
            ->method('getEmail')
            ->will($this->returnValue('testemail2@example.com'));

        $this->mockQuery->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$this->mockUserAccount, $this->mockUserAccount]));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $users = $userService->getUsers();
        $expectedUsers = [
            [
                'id' => 3,
                'email' => 'testemail2@example.com',
            ],
            [
                'id' => 3,
                'email' => 'testemail2@example.com',
            ],
        ];

        $this->assertEquals($expectedUsers, $users);
    }

    public function testGetUserByEmail()
    {
        $this->mockUserAccountRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->mockQueryBuilder));

        $this->mockQueryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->mockQuery));

        $this->mockQueryBuilder->expects($this->once())
            ->method('where');

        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserAccount->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('testemail2@example.com'));

        $this->mockQuery->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$this->mockUserAccount]));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $users = $userService->getUsers(['email' => 'testemail2@example.com']);
        $expectedUsers = [
            [
                'id' => 3,
                'email' => 'testemail2@example.com',
            ],
        ];

        $this->assertEquals($expectedUsers, $users);
    }


    public function testPasswordComplexityCheckValid()
    {
        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $validPassword = $userService->verifyPasswordComplexity('Password1');
        $this->assertEquals([], $validPassword);
    }

    public function testPasswordComplexityCheckInvalid()
    {
        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $validPassword = $userService->verifyPasswordComplexity('passwor');
        $this->assertEquals(['be 8 characters or more', 'include a number', 'include a capital letter'], $validPassword);
    }

    public function testUserPasswordSetViaOneTimePasswordSetTokenNewUser()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('validateOneTimePasswordSetToken')
            ->will($this->returnValue(true));

        $this->mockUserAccount->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue(UserAccount::STATUS_NOT_ACTIVATED));

        $this->mockUserAccount->expects($this->once())
            ->method('setStatus')
            ->with(UserAccount::STATUS_ACTIVE);

        $this->mockUserAccount->expects($this->once())
            ->method('setPassword')
            ->with('Password1');

        $this->mockUserAccount->expects($this->once())
            ->method('clearOneTimePasswordSetToken');

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaOneTimeToken(
            'testOneTimePasswordSetToken',
            3,
            'Password1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_200,
            'errors' => [],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaOneTimePasswordSetTokenExistingUser()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('validateOneTimePasswordSetToken')
            ->will($this->returnValue(true));

        $this->mockUserAccount->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue(UserAccount::STATUS_ACTIVE));

        $this->mockUserAccount->expects($this->never())
            ->method('setStatus');

        $this->mockUserAccount->expects($this->once())
            ->method('setPassword')
            ->with('Password1');

        $this->mockUserAccount->expects($this->once())
            ->method('clearOneTimePasswordSetToken');

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaOneTimeToken(
            'testOneTimePasswordSetToken',
            3,
            'Password1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_200,
            'errors' => [],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaOneTimePasswordSetTokenInsufficientlyComplexPassword()
    {
        $this->mockUserAccount->expects($this->never())
            ->method('validateOneTimePasswordSetToken');

        $this->mockUserAccount->expects($this->never())
            ->method('getStatus');

        $this->mockUserAccount->expects($this->never())
            ->method('setStatus');

        $this->mockUserAccount->expects($this->never())
            ->method('setPassword');

        $this->mockUserAccount->expects($this->never())
            ->method('clearOneTimePasswordSetToken');

        $this->mockEntityManager->expects($this->never())
            ->method('persist');

        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaOneTimeToken(
            'testOneTimePasswordSetToken',
            3,
            'password'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_400,
            'errors' => [
                'password' => 'Password does not meet complexity requirement',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaOneTimePasswordSetTokenInvalidToken()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('validateOneTimePasswordSetToken')
            ->will($this->returnValue(false));

        $this->mockUserAccount->expects($this->never())
            ->method('getStatus');

        $this->mockUserAccount->expects($this->never())
            ->method('setStatus');

        $this->mockUserAccount->expects($this->never())
            ->method('setPassword');

        $this->mockUserAccount->expects($this->never())
            ->method('clearOneTimePasswordSetToken');

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockEntityManager->expects($this->never())
            ->method('persist');

        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaOneTimeToken(
            'testOneTimePasswordSetToken',
            3,
            'Password1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_401,
            'errors' => [
                'one-time-password-set-token' => 'One-time password set token is invalid',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaOneTimePasswordSetTokenNonexistentUser()
    {
        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue(false));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaOneTimeToken(
            'testOneTimePasswordSetToken',
            3,
            'Password1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_404,
            'errors' => [
                'user' => 'User does not exist',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaExistingPassword()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockUserAccount->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue('$2y$10$nKhc9i6Op3szG5GBIhwJn.0VJqj/Sz8tZI8zIFhSDeWVjdnGMriYC'));

        $this->mockUserAccount->expects($this->once())
            ->method('setPassword')
            ->with('newPassword1');

        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaExistingPassword(
            $this->mockUserAccount,
            'Password1',
            'newPassword1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_200,
            'errors' => [],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function test_set_password_for_user_via_existing_password_throws_404_when_the_user_is_missing()
    {
        $this->mockEntityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccountRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaExistingPassword(
            $this->mockUserAccount,
            'Password1',
            'newPassword1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_404,
            'errors' => [
                'user' => 'User does not exist',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaExistingPasswordInsufficientlyComplexPassword()
    {
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaExistingPassword(
            $this->mockUserAccount,
            'existingPassword1',
            'invalidNewPassword'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_400,
            'errors' => [
                'password' => 'Password must include a number',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserPasswordSetViaExistingPasswordInvalidExistingPassword()
    {
        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockUserAccount->expects($this->once())
            ->method('getPassword')
            ->will($this->returnValue('existingPassword1'));

        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $errorArray = $userService->setPasswordForUserViaExistingPassword(
            $this->mockUserAccount,
            'incorrectPassword',
            'newPassword1'
        );

        $expectedErrorArray = [
            'status' => Response::STATUS_CODE_400,
            'errors' => [
                'password' => 'Password supplied was incorrect or user is not active',
            ],
        ];

        $this->assertEquals($expectedErrorArray, $errorArray);
    }

    public function testUserAccountRetrievalById()
    {
        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));


        $userService = new UserService(
            $this->mockEntityManager,
            $this->userServiceConfig
        );

        $userAccount = $userService->retrieveUserAccountEntity(3);

        $this->assertSame($this->mockUserAccount, $userAccount);
    }
}
