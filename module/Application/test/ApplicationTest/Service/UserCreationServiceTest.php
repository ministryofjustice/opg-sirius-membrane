<?php

namespace ApplicationTest\Service;

use Application\Exception\UserAlreadyExistsException;
use Application\Model\Entity\UserAccount;
use Application\Service\UserCreationService;
use PHPUnit\Framework\TestCase;

class UserCreationServiceTest extends TestCase
{
    protected $mockEntityManager;

    protected $mockUserEmailService;

    public function setup(): void
    {
        parent::setUp();

        $this->mockEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockUserEmailService = $this->getMockBuilder('Application\Service\UserEmailService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testUserAdminCreationWithPassword()
    {
        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $userCreationService = new UserCreationService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );
        $newUser = $userCreationService->createUser('EMAIL@EXAMPLE.COM', 'Password1', true);

        // Check that user has been created with parameters specified.
        $this->assertEquals('email@example.com', $newUser->getEmail());
        $this->assertTrue($newUser->isAdmin());
        $this->assertEquals(UserAccount::STATUS_ACTIVE, $newUser->getStatus());
    }

    public function testUserNonAdminCreationWithPassword()
    {
        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $userCreationService = new UserCreationService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );
        $newUser = $userCreationService->createUser('email@example.com', 'Password1', false);

        // Check that user has been created with parameters specified.
        $this->assertEquals('email@example.com', $newUser->getEmail());
        $this->assertFalse($newUser->isAdmin());
        $this->assertEquals(UserAccount::STATUS_ACTIVE, $newUser->getStatus());
    }

    public function testUserNonAdminCreationWithoutPassword()
    {
        // Set up mock Entity Manager to simulate successful user creation.
        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockUserEmailService->expects($this->once())
            ->method('sendActivationEmail');

        $userCreationService = new UserCreationService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );

        $newUser = $userCreationService->createUser('EMAIL@example.com', null, false);

        // Check that user has been created with parameters specified.
        $this->assertEquals('email@example.com', $newUser->getEmail());
        $this->assertFalse($newUser->isAdmin());
        $this->assertEquals(UserAccount::STATUS_NOT_ACTIVATED, $newUser->getStatus());
    }

    public function testExistingUserThrowsException()
    {
        $mockUniqueConstraintViolationException =
            $this->getMockBuilder('Doctrine\DBAL\Exception\UniqueConstraintViolationException')
            ->disableOriginalConstructor()
            ->getMock();

        // Set up mock Entity Manager to throw exception when writing to database.
        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush')
            ->will($this->throwException($mockUniqueConstraintViolationException));

        $userCreationService = new UserCreationService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('User already exists');

        $userCreationService->createUser('email@example.com', 'Password1', false);
    }

    public function testEmptyEmailParameterThrowsException()
    {
        // Set up mock Entity Manager to throw exception when writing to database.
        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush')
            ->will($this->throwException(new \Exception));

        $userCreationService = new UserCreationService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );

        $this->expectException(\Exception::class);
        $userCreationService->createUser('', 'Password1', false);
    }

    public function testResendingOfActivationEmail()
    {
        $mockUser = $this->createMock('Application\Model\Entity\UserAccount');
        $mockUser->expects($this->once())
            ->method('setOneTimePasswordSetToken');
        $mockUser->expects($this->once())
            ->method('setStatus');

        $mockRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepository
            ->method('find')
            ->will($this->returnValue($mockUser));

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockEntityManager
            ->method('getRepository')
            ->will($this->returnValue($mockRepository));

        $this->mockUserEmailService->expects($this->once())
            ->method('sendActivationEmail');

        $userCreationService = new UserCreationService($this->mockEntityManager, $this->mockUserEmailService);

        $userCreationService->resendActivationEmail($mockUser);
    }
}
