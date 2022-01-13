<?php

declare(strict_types=1);

namespace ApplicationTest\Service;

use Application\Authentication\Adapter\LockedUser;
use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\UserSessionService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use InvalidArgumentException;
use JwtLaminasAuth\Authentication\Storage\JwtStorage;
use JwtLaminasAuth\Service\JwtService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Authentication\Storage\StorageInterface;
use Laminas\Http\Response;
use Laminas\Session\SessionManager;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserSessionServiceTest extends TestCase
{
    /**
     * @var AuthenticationService|MockObject
     */
    protected $mockAuthenticationService;

    /**
     * @var AuthenticationServiceConstructor|MockObject
     */
    protected $mockAuthenticationServiceConstructor;

    /**
     * @var SessionManager|MockObject
     */
    protected $mockSessionManager;

    /**
     * @var StorageInterface|MockObject
     */
    protected $mockAuthenticationStorageService;

    /**
     * @var ObjectRepository|MockObject
     */
    protected $mockAuthenticationAdapterService;

    /**
     * @var Result|MockObject
     */
    protected $mockAuthenticationResult;

    /**
     * @var EntityManager|MockObject
     */
    protected $mockEntityManager;

    /**
     * @var EntityRepository|MockObject
     */
    protected $mockUserAccountRepository;

    /**
     * @var UserAccount|MockObject
     */
    protected $mockUserAccount;

    /**
     * @var JwtService|MockObject
     */
    private $jwtService;

    public function setup(): void
    {
        $this->mockAuthenticationService = $this->createMock(AuthenticationService::class);
        $this->mockAuthenticationServiceConstructor = $this->createMock(AuthenticationServiceConstructor::class);
        $this->mockAuthenticationServiceConstructor->method('getNormal')->willReturn($this->mockAuthenticationService);
        $this->mockAuthenticationServiceConstructor->method('getBypassMembrane')->willReturn($this->mockAuthenticationService);
        $this->mockAuthenticationAdapterService = $this->createMock(
            ObjectRepository::class
        );
        $this->mockAuthenticationStorageService = $this->createMock(
            StorageInterface::class
        );
        $this->mockSessionManager = $this->createMock(
            SessionManager::class
        );
        $this->mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccountRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockAuthenticationResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserAccount = $this->createMock(UserAccount::class);

        $this->jwtService = $this->createMock(JwtService::class);
    }

    public function test_response_from_authentication_result_success()
    {
        $email = 'test@test.com';
        $password = 'password';
        $jwtExpiry = 3600;

        /** @var UserAccount|MockObject */
        $userAccount = $this->createMock(UserAccount::class);
        $userAccount->method('getId')->willReturn(365);
        $userAccount->expects($this->once())->method('setLastLoggedInValue');

        $result = new Result(Result::SUCCESS, $userAccount, []);

        $this->mockAuthenticationAdapterService->expects($this->once())
            ->method('setIdentity')
            ->with($email)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationAdapterService->expects($this->once())
            ->method('setCredential')
            ->with($password)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects($this->once())
            ->method('getAdapter')
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects($this->once())
            ->method('authenticate')
            ->willReturn($result);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($userAccount);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush');

        $this->jwtService->expects($this->never())->method('createSignedToken');

        $this->mockSessionManager->expects($this->any())->method('getId')->willReturn('SessionId');

        $sut = new UserSessionService(
            $this->mockAuthenticationServiceConstructor,
            $this->mockSessionManager,
            $this->mockEntityManager,
            $jwtExpiry,
            $this->jwtService,
            false
        );

        $response = $sut->openUserSession($email, $password, false);

        $this->assertEquals([
            'status' => Response::STATUS_CODE_201,
            'body' => [
                'email' => 'test@test.com',
                'userId' => 365,
                'authentication_token' => 'SessionId',
            ],
        ], $response);
    }

    public function test_response_from_authentication_result_success_return_jwt()
    {
        $email = 'test@test.com';
        $password = 'password';
        $jwtExpiry = 3600;

        /** @var UserAccount|MockObject */
        $userAccount = $this->createMock(UserAccount::class);
        $userAccount->method('getId')->willReturn(365);
        $userAccount->expects($this->once())->method('setLastLoggedInValue');

        $result = new Result(Result::SUCCESS, $userAccount, []);

        $this->mockAuthenticationAdapterService->expects($this->once())
            ->method('setIdentity')
            ->with($email)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationAdapterService->expects($this->once())
            ->method('setCredential')
            ->with($password)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects($this->once())
            ->method('getAdapter')
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects($this->once())
            ->method('authenticate')
            ->willReturn($result);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($userAccount);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush');

        $mockToken = $this->createMock(Token::class);
        $mockToken->expects($this->any())
            ->method('toString')
            ->willReturn('JwtToken');

        $this->jwtService->expects($this->once())
            ->method('createSignedToken')
            ->with(JwtStorage::SESSION_CLAIM_NAME, $email, $jwtExpiry)
            ->willReturn($mockToken);

        $this->mockSessionManager->expects($this->any())->method('getId')->willReturn('SessionId');

        $sut = new UserSessionService(
            $this->mockAuthenticationServiceConstructor,
            $this->mockSessionManager,
            $this->mockEntityManager,
            $jwtExpiry,
            $this->jwtService,
            false
        );

        $response = $sut->openUserSession($email, $password, true);

        $this->assertEquals([
            'status' => Response::STATUS_CODE_201,
            'body' => [
                'email' => 'test@test.com',
                'userId' => 365,
                'authentication_token' => 'SessionId',
                'jwt' => 'JwtToken',
            ],
        ], $response);
    }

    /**
     * @dataProvider provideResponseResultPairs
     * @param Result $result
     * @param array $expectedResponse
     * @param bool $returnJwt
     */
    public function test_response_from_authentication_result(Result $result, array $expectedResponse, bool $returnJwt = false)
    {
        $email = 'test@test.com';
        $password = 'password';
        $jwtExpiry = 3600;

        $this->mockAuthenticationAdapterService->expects(self::once())
            ->method('setIdentity')
            ->with($email)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationAdapterService->expects(self::once())
            ->method('setCredential')
            ->with($password)
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects(self::once())
            ->method('getAdapter')
            ->willReturn($this->mockAuthenticationAdapterService);
        $this->mockAuthenticationService->expects(self::once())
            ->method('authenticate')
            ->willReturn($result);

        $this->mockEntityManager
            ->expects($this->never())
            ->method('persist');

        $this->mockEntityManager->expects(self::once())->method('flush');

        $mockToken = $this->createMock(Token::class);
        $mockToken->expects($this->any())
            ->method('toString')
            ->willReturn('JwtToken');

        $this->jwtService->expects(self::any())
            ->method('createSignedToken')
            ->with(JwtStorage::SESSION_CLAIM_NAME, $email, $jwtExpiry)
            ->willReturn($mockToken);

        $this->mockSessionManager->expects(self::any())->method('getId')->willReturn('SessionId');

        $sut = new UserSessionService(
            $this->mockAuthenticationServiceConstructor,
            $this->mockSessionManager,
            $this->mockEntityManager,
            $jwtExpiry,
            $this->jwtService,
            false
        );

        $response = $sut->openUserSession($email, $password, $returnJwt);

        self::assertEquals($expectedResponse, $response);
    }

    public function provideResponseResultPairs()
    {
        $identity = new UserAccount();
        $identity->setId(365);

        return [
            [
                new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, ['Identity not found']),
                [
                    'status' => Response::STATUS_CODE_401,
                    'body' => [
                        'error' => 'Invalid email or password.',
                        'userId' => null,
                    ],
                ],
            ],
            [
                new Result(Result::FAILURE_CREDENTIAL_INVALID, $identity, ['Credential invalid']),
                [
                    'status' => Response::STATUS_CODE_401,
                    'body' => [
                        'error' => 'Invalid email or password.',
                        'userId' => 365,
                    ],
                ],
            ],
            [
                new Result(LockedUser::FAILURE_ACCOUNT_LOCKED, $identity, ['Account Locked']),
                [
                    'status' => Response::STATUS_CODE_403,
                    'body' => [
                        'userId' => 365,
                        'error' => 'Unsuccessful login attempts exceeded.',
                        'locked' => true,
                    ],
                ],
            ],
        ];
    }

    public function testCanDeleteOwnSession()
    {
        // Set up mock SessionManager object to return a session ID.
        $this->mockSessionManager->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('validsessionid'));

        // Set up mock Authentication Service to perform authentication.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('clearIdentity');

        $this->createUserSessionService()->closeUserSession('validsessionid', false);
    }

    public function testCanNotDeleteAnotherSession()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User session id ('mysessionid') does not match the given session id ('notmysessionid')");

        // Set up mock SessionManager object to return a session ID.
        $this->mockSessionManager->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue('mysessionid'));

        // Set up mock Authentication Service to perform authentication.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->createUserSessionService()->closeUserSession('notmysessionid', false);
    }

    public function testCanNotDeleteSessionWhenNotAuthenticated()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User is not logged in");

        // Set up mock SessionManager object to return a session ID.
        $this->mockSessionManager->expects($this->never())
            ->method('getId');

        // Set up mock Authentication Service to perform authentication.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(false));

        $this->createUserSessionService()->closeUserSession('invalidsessionid', false);
    }

    public function testGetSessionId()
    {
        $this->mockSessionManager->expects($this->once())
            ->method('getId')
            ->willReturn('Session #1');

        $sessionId = $this->createUserSessionService()->getSessionId();

        $this->assertEquals('Session #1', $sessionId);
    }

    private function createUserSessionService(): UserSessionService
    {
        $jwtExpiry = 1800;

        return new UserSessionService(
            $this->mockAuthenticationServiceConstructor,
            $this->mockSessionManager,
            $this->mockEntityManager,
            $jwtExpiry,
            $this->jwtService,
            false
        );
    }
}
