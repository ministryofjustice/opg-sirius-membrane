<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\UserRestController;
use Application\Exception\UserAlreadyExistsException;
use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserCreationService;
use Application\Service\UserService;
use Application\Service\UserUpdateService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Headers;
use Laminas\Http\Response;
use Laminas\Log\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class UserRestControllerTest extends BaseControllerTestCase
{
    protected $mockAuthenticationService;
    protected $mockUserCreationService;
    protected $mockUserUpdateService;
    protected $mockUserService;
    protected $mockUserAccount;
    private $logger;
    protected $mockAuthenticationServiceConstructor;

    /**
     * @var SecurityLogger|MockObject
     */
    private $securityLogger;

    public function setup(): void
    {
        parent::setUp();

        // Set up mock replacement services so they can be manipulated for testing.
        $this->mockAuthenticationService = $this->createMock(AuthenticationService::class);
        $this->mockUserCreationService = $this->getMockBuilder(UserCreationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUserUpdateService = $this->getMockBuilder(UserUpdateService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUserService = $this->getMockBuilder(UserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUserAccount = $this->createMock(UserAccount::class);
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAuthenticationServiceConstructor = $this->createMock(AuthenticationServiceConstructor::class);
        $this->mockAuthenticationServiceConstructor->method('getNormal')->willReturn($this->mockAuthenticationService);
        $this->mockAuthenticationServiceConstructor->method('getBypassMembrane')->willReturn($this->mockAuthenticationService);

        $this->securityLogger = $this->createMock(SecurityLogger::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(AuthenticationServiceConstructor::class, $this->mockAuthenticationServiceConstructor);
        $serviceManager->setService(UserService::class, $this->mockUserService);
        $serviceManager->setService(UserCreationService::class, $this->mockUserCreationService);
        $serviceManager->setService(UserUpdateService::class, $this->mockUserUpdateService);
        $serviceManager->setService(Logger::class, $this->logger);
        $serviceManager->setService(SecurityLogger::class, $this->securityLogger);
    }

    public function testGetAllUsers()
    {
        $userCollection = [
            ['id' => 1, 'email' => 'testuser1@example.com'],
            ['id' => 2, 'email' => 'testuser2@example.com'],
        ];

        $this->mockUserService->expects($this->once())
            ->method('getUsers')
            ->will($this->returnValue($userCollection));

        $this->dispatchJsonUserGetRequest();

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals(json_encode($userCollection), $this->getResponse()->getContent());
    }

    public function testGetUserByEmail()
    {
        $userCollection = [
            ['id' => 2, 'email' => 'testuser2@example.com'],
        ];

        $this->mockUserService->expects($this->once())
            ->method('getUsers')
            ->with(['email' => 'testuser2@example.com'])
            ->will($this->returnValue($userCollection));

        $this->dispatchJsonUserGetRequest(['email' => 'testuser2@example.com']);

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals(json_encode($userCollection), $this->getResponse()->getContent());
    }


    public function testUnauthenticatedUserCannotCreateUser()
    {
        // Set up mock Authentication Service to reflect unauthenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(false));

        $this->dispatchJsonUserCreationRequest('username', 'password');
        $this->assertResponseStatusCode(Response::STATUS_CODE_402);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testAuthenticatedNonAdminUserCannotCreateUser()
    {
        // Set up mock User Account to NOT be an admin.
        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->dispatchJsonUserCreationRequest('username', 'password');
        $this->assertResponseStatusCode(Response::STATUS_CODE_402);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testAuthenticatedAdminUserCanCreateAdminUser()
    {
        $newUser = new UserAccount();
        $newUser->setEmail('email@example.com');

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

        $this->mockUserService->expects($this->once())
            ->method('verifyPasswordComplexity')
            ->with('Password1')
            ->will($this->returnValue([]));

        // Setup mock User Service to test arguments and return new user account
        $this->mockUserCreationService->expects($this->once())
            ->method('createUser')
            ->with('email@example.com', 'Password1', true)
            ->will($this->returnValue($newUser));

        $this->dispatchJsonUserCreationRequest('email@example.com', 'Password1', ['System Admin']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_201);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testAuthenticatedAdminUserCanCreateNonAdminUser()
    {
        $newUser = new UserAccount();
        $newUser->setEmail('email@example.com');

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

        $this->mockUserService->expects($this->once())
            ->method('verifyPasswordComplexity')
            ->with('Password1')
            ->will($this->returnValue([]));

        // Setup mock User Service to test arguments and return new user account
        $this->mockUserCreationService->expects($this->once())
            ->method('createUser')
            ->with('email@example.com', 'Password1', false)
            ->will($this->returnValue($newUser));

        $this->dispatchJsonUserCreationRequest('email@example.com', 'Password1', ['Wizard']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_201);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testAuthenticatedAdminUserCanCreateUserWithoutPassword()
    {
        $newUser = new UserAccount();
        $newUser->setEmail('email@example.com');

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

        // Setup mock User Service to test arguments and return new user account
        $this->mockUserCreationService->expects($this->once())
            ->method('createUser')
            ->with('email@example.com', null, false)
            ->will($this->returnValue($newUser));

        $this->dispatchJsonUserCreationRequest('email@example.com', null, ['Wizard']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_201);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testUserCannotBeSavedErrorReturns400OnCreate()
    {
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

        $this->mockUserService->expects($this->once())
            ->method('verifyPasswordComplexity')
            ->with('Password1')
            ->will($this->returnValue([]));

        // Setup mock User Service to test arguments and return new user account
        $this->mockUserCreationService->expects($this->once())
            ->method('createUser')
            ->with('email@example.com', 'Password1', false)
            ->will($this->throwException(new UserAlreadyExistsException()));

        $this->dispatchJsonUserCreationRequest('email@example.com', 'Password1', ['Wizard']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testInvalidEmailReturns400OnCreate()
    {
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

        $this->dispatchJsonUserCreationRequest('invalidemail', 'password', ['Wizard']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testInvalidPasswordReturns400OnCreate()
    {
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

        $this->mockUserService->expects($this->once())
            ->method('verifyPasswordComplexity')
            ->with('password')
            ->will($this->returnValue(['so many problems']));

        $this->dispatchJsonUserCreationRequest('email@example.com', 'password', ['Wizard']);
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testUpdateUserUnauthenticatedUser()
    {
        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(false));

        $this->dispatchJsonUserUpdateRequest(65, []);
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testUpdateUserAuthenticatedNonAdminUser()
    {
        $this->mockUserAccount->expects($this->once())
             ->method('getId')
             ->will($this->returnValue(2));

        // Set up mock User Account to be an admin.
        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->dispatchJsonUserUpdateRequest(65, []);
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testUpdateUserAuthenticatedUserEditingOwnAccount()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(65));

        // Set up mock User Account to be an admin.
        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserUpdateService->expects($this->once())
            ->method('updateUser')
            ->will($this->returnValue(['body' => ['response'], 'status' => 200]));

        $this->dispatchJsonUserUpdateRequest(65, []);
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testUpdateUserAuthenticatedAdminUserSuspend()
    {
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

        $newData = ['status' => UserAccount::STATUS_SUSPENDED, 'roles' => ['OPGUser']];

        $this->mockUserUpdateService->expects($this->once())
            ->method('updateUser')
            ->with($this->equalTo(65), $this->equalTo($newData))
            ->will($this->returnValue(
                [
                    'status' => Response::STATUS_CODE_200,
                    'body' => [],
                ]
            ));

        $this->dispatchJsonUserUpdateRequest(65, $newData);
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testUpdateUserAuthenticatedAdminUserUnsuspend()
    {
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

        $newData = ['status' => UserAccount::STATUS_ACTIVE, 'roles' => ['OPGUser']];

        $this->mockUserUpdateService->expects($this->once())
            ->method('updateUser')
            ->with($this->equalTo(65), $this->equalTo($newData))
            ->will($this->returnValue(
                [
                    'status' => Response::STATUS_CODE_200,
                    'body' => [],
                ]
            ));

        $this->dispatchJsonUserUpdateRequest(65, $newData);
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testInvalidRolesReturns400OnCreate()
    {
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

        $this->dispatchJsonUserCreationRequest('email@example.com', 'Password1', 'junk');
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
        $this->assertControllerName(UserRestController::class);
        $this->assertMatchedRouteName('user-service');
    }

    public function testSuccessfulPasswordAndStatusSetOnNonActivatedUserViaOneTimePasswordResetToken()
    {
        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaOneTimeToken')
            ->with('oneTimePasswordSetToken', 3, 'Password1')
            ->will($this->returnValue([
                'status' => Response::STATUS_CODE_200,
                'errors' => [],
            ]));

        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->logicalOr(
                'Successful password update via single-use token',
                $this->anything()
            ));

        $this->dispatchJsonUserUpdateViaOneTimePasswordSetTokenRequest(
            3,
            'Password1',
            'oneTimePasswordSetToken'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testInvalidOneTimePasswordSetTokenReturns401OnUpdateViaOneTimePasswordResetToken()
    {
        $invalidTokenError = [
            'status' => Response::STATUS_CODE_401,
            'errors' => [
                'one-time-password-set-token' => 'One-time password set token is invalid',
            ],
        ];

        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaOneTimeToken')
            ->with('oneTimePasswordSetToken', 3, 'Password1')
            ->will($this->returnValue($invalidTokenError));

        $this->securityLogger->expects($this->once())
            ->method('passwordUpdateViaSingleUseTokenFailed')
            ->with(3, null);

        $this->dispatchJsonUserUpdateViaOneTimePasswordSetTokenRequest(
            3,
            'Password1',
            'oneTimePasswordSetToken'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testPasswordOfInsufficientComplexityReturns400OnUpdateViaOneTimePasswordResetToken()
    {
        $invalidTokenError = [
            'status' => Response::STATUS_CODE_400,
            'errors' => [
                'password' => 'Password does not meet complexity requirement',
            ],
        ];

        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaOneTimeToken')
            ->with('oneTimePasswordSetToken', 3, 'password')
            ->will($this->returnValue($invalidTokenError));

        $this->securityLogger->expects($this->once())
            ->method('passwordUpdateViaSingleUseTokenFailed')
            ->with(3, null);

        $this->dispatchJsonUserUpdateViaOneTimePasswordSetTokenRequest(
            3,
            'password',
            'oneTimePasswordSetToken'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
    }

    public function testNonexistentUserReturns404OnUpdateViaOneTimePasswordResetToken()
    {
        $invalidTokenError = [
            'status' => Response::STATUS_CODE_404,
            'errors' => [
                'user' => 'User does not exist',
            ],
        ];

        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaOneTimeToken')
            ->with('oneTimePasswordSetToken', 3, 'password')
            ->will($this->returnValue($invalidTokenError));

        $this->securityLogger->expects($this->atLeastOnce())
            ->method('passwordUpdateViaSingleUseTokenFailed')
            ->with(3, 'User does not exist');

        $this->dispatchJsonUserUpdateViaOneTimePasswordSetTokenRequest(
            3,
            'password',
            'oneTimePasswordSetToken'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_404);
    }

    public function testSuccessfulPasswordSetOnUserViaExistingPassword()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaExistingPassword')
            ->with($this->mockUserAccount, 'existingPassword', 'newPassword')
            ->will($this->returnValue([
                'status' => Response::STATUS_CODE_200,
                'errors' => [],
            ]));

        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->logicalOr(
                'Successful password update via supplied password',
                $this->anything()
            ));

        $this->dispatchJsonUserUpdateViaExistingPasswordRequest(
            3,
            'newPassword',
            'existingPassword'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testPasswordNotSetOnAnotherUnauthenticatedUserViaExistingPassword()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(false));

        $this->mockUserService->expects($this->never())
            ->method('setPasswordForUserViaExistingPassword');

        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'Attempting to update user account without authorisation',
                $this->anything()
            );

        $this->dispatchJsonUserUpdateViaExistingPasswordRequest(
            3,
            'newPassword',
            'existingPassword'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testPasswordNotSetOnAnotherAccountUserViaExistingPassword()
    {
        // Set up mock Authentication Service to reflect authenticated user.
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(4));

        $this->mockUserService->expects($this->never())
            ->method('setPasswordForUserViaExistingPassword');

        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'User attempting to change password on account that is not their own',
                $this->anything()
            );

        $this->dispatchJsonUserUpdateViaExistingPasswordRequest(
            3,
            'newPassword',
            'existingPassword'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    public function testPasswordNotSetWhenExistingPasswordNotSuppliedViaExistingPassword()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserService->expects($this->never())
            ->method('setPasswordForUserViaExistingPassword');

        $this->logger->expects($this->once())
            ->method('err')
            ->with(
                'Existing password was not supplied and thus could not be verified',
                $this->anything()
            );

        $this->dispatchJsonUserUpdateViaExistingPasswordRequest(
            3,
            'newPassword'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
    }

    public function testPasswordNotSetWhenNewPasswordInvalidViaExistingPassword()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $this->mockUserService->expects($this->once())
            ->method('setPasswordForUserViaExistingPassword')
            ->will($this->returnValue(
                [
                    'status' => 400,
                    'errors' => ['some error'],
                ]
            ));

        $this->securityLogger->expects($this->once())
            ->method('passwordUpdateViaSuppliedPasswordFailed')
            ->with(3, ['some error']);

        $this->dispatchJsonUserUpdateViaExistingPasswordRequest(
            3,
            'a',
            'existingPassword'
        );
        $this->assertResponseStatusCode(Response::STATUS_CODE_400);
    }

    public function testDeleteUser()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        $this->mockUserUpdateService->expects($this->once())
            ->method('deleteUser')
            ->with(76);

        $this->dispatch('/auth/users/76', 'DELETE');
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testOnlyAdminsCanDeleteUser()
    {
        $this->mockAuthenticationService->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->mockAuthenticationService->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserAccount->expects($this->once())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $this->dispatch('/auth/users/76', 'DELETE');
        $this->assertResponseStatusCode(Response::STATUS_CODE_401);
    }

    protected function dispatchJsonUserGetRequest(array $urlParams = null)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('GET');


        $request->setContent(json_encode([]));

        $url = '/auth/users';
        if ($urlParams) {
            $url .= '?' . http_build_query($urlParams);
        }

        $this->dispatch($url);
    }

    protected function dispatchJsonUserCreationRequest($username, $password, $roles = [])
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('POST');
        $payload = [
            'user' => [
                'email' => $username,
                'password' => $password,
                'roles' => $roles,
            ],
        ];

        $request->setContent(json_encode($payload));

        $this->dispatch('/auth/users');
    }

    protected function dispatchJsonUserUpdateRequest($id, $newData)
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('PUT');

        $request->setContent(json_encode($newData));

        $this->dispatch("/auth/users/$id");
    }

    protected function dispatchJsonUserUpdateViaOneTimePasswordSetTokenRequest(
        $uid,
        $password,
        $oneTimePasswordSetToken
    ) {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');
        $headers->addHeaderLine('Sirius-One-Time-Password-Set-Token', $oneTimePasswordSetToken);

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('PATCH');
        $payload = ['password' => $password,];

        $request->setContent(json_encode($payload));

        $this->dispatch("/auth/users/$uid");
    }

    protected function dispatchJsonUserUpdateViaExistingPasswordRequest(
        $uid,
        $newPassword,
        $existingPassword = null
    ) {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');
        if ($existingPassword) {
            $headers->addHeaderLine('Sirius-Existing-Password', $existingPassword);
        }

        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod('PATCH');
        $payload = ['password' => $newPassword];

        $request->setContent(json_encode($payload));

        $this->dispatch("/auth/users/$uid");
    }
}
