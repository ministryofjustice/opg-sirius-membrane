<?php

namespace ApplicationTest\Service;

use Application\Service\UserPasswordResetService;
use PHPUnit\Framework\TestCase;

class UserPasswordResetServiceTest extends TestCase
{
    protected $mockEntityManager;

    protected $mockUserAccountRepository;

    protected $mockUserAccount;

    protected $mockUserEmailService;

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

        $this->mockUserEmailService = $this->getMockBuilder('Application\Service\UserEmailService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testUserPasswordResetViaEmail()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockEntityManager->expects($this->once())
            ->method('persist');

        $this->mockEntityManager->expects($this->once())
            ->method('flush');

        $this->mockUserAccount->expects($this->once())
            ->method('setOneTimePasswordSetToken');

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue($this->mockUserAccount));

        $this->mockUserEmailService->expects($this->once())
            ->method('sendPasswordResetEmail');

        $userPasswordResetService = new UserPasswordResetService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );
        $passwordResetResult = $userPasswordResetService->sendPasswordResetViaEmail(3);
        $expectedResult = [
            'errors' => [],
        ];

        // Check that user has been created with parameters specified.
        $this->assertEquals($expectedResult, $passwordResetResult);
    }

    public function testUserPasswordResetViaEmailNonExistentUser()
    {
        $this->mockEntityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->mockUserAccountRepository));

        $this->mockEntityManager->expects($this->never())
            ->method('persist');

        $this->mockEntityManager->expects($this->never())
            ->method('flush');

        $this->mockUserAccountRepository->expects($this->once())
            ->method('find')
            ->with(3)
            ->will($this->returnValue(null));

        $userPasswordResetService = new UserPasswordResetService(
            $this->mockEntityManager,
            $this->mockUserEmailService
        );
        $passwordResetResult = $userPasswordResetService->sendPasswordResetViaEmail(3);
        $expectedResult = [
            'errors' => [
                'user' => 'User does not exist',
            ],
        ];

        // Check that user has been created with parameters specified.
        $this->assertEquals($expectedResult, $passwordResetResult);
    }
}
