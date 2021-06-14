<?php

declare(strict_types=1);

namespace ApplicationTest\Authentication\Adapter;


use Application\Authentication\Adapter\ChainingAdapterInterface;
use Application\Authentication\Adapter\LockedUser;
use Application\Model\Entity\UserAccount;
use Application\Service\SecurityLogger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Mockobject\MockObject;
use Laminas\Authentication\Result;

class LockedUserTest extends TestCase
{
    /**
     * @var ChainingAdapterInterface|MockObject
     */
    private $mockAdapter;

    /**
     * @var SecurityLogger|MockObject
     */
    private $mockSecurityLogger;

    public function setup(): void
    {
        $this->mockAdapter = $this->createMock(ChainingAdapterInterface::class);

        $this->mockSecurityLogger = $this->createMock(SecurityLogger::class);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $validIdentity = 'test-user@opgtest.com';
        $validPassword = 'correctPassword';

        $identity = new UserAccount();
        $identity->setId(1);
        $identity->setEmail($validIdentity);
        $identity->setPassword($validPassword);
        $identity->setStatus(UserAccount::STATUS_ACTIVE);

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->mockAdapter->method('authenticate')->willReturn($result);
        $this->mockAdapter->method('setIdentity')->with($validIdentity);
        $this->mockAdapter->method('setCredential')->with($validPassword);

        $sut = new LockedUser($this->mockAdapter, $this->mockSecurityLogger, 3);
        $sut->setIdentity($validIdentity);
        $sut->setCredential($validPassword);

        $newResult = $sut->authenticate();

        self::assertEquals($result, $newResult);
        self::assertEquals(false, $identity->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function test_user_strikes_are_incremented_if_result_fails()
    {
        $validIdentity = 'test-user@opgtest.com';
        $invalidPassword = 'incorrectPassword';

        $identity = new UserAccount();
        $identity->setId(1);
        $identity->setEmail($validIdentity);
        $identity->setPassword($invalidPassword);
        $identity->setStatus(UserAccount::STATUS_ACTIVE);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, $identity, []);

        $this->mockAdapter->method('authenticate')->willReturn($result);
        $this->mockAdapter->method('setIdentity')->with($validIdentity);
        $this->mockAdapter->method('setCredential')->with($invalidPassword);

        $sut = new LockedUser($this->mockAdapter, $this->mockSecurityLogger, 3);
        $sut->setIdentity($validIdentity);
        $sut->setCredential($invalidPassword);

        $newResult = $sut->authenticate();

        self::assertEquals($result, $newResult);
        self::assertEquals(true, $identity->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function test_user_is_locked_after_max_failures()
    {
        $validIdentity = 'test-user@opgtest.com';
        $invalidPassword = 'incorrectPassword';

        $identity = new UserAccount();
        $identity->setId(1);
        $identity->setEmail($validIdentity);
        $identity->setPassword($invalidPassword);
        $identity->setStatus(UserAccount::STATUS_ACTIVE);

        $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, $identity, []);

        $this->mockAdapter->method('authenticate')->willReturn($result);
        $this->mockAdapter->method('setIdentity')->with($validIdentity);
        $this->mockAdapter->method('setCredential')->with($invalidPassword);

        $this->mockSecurityLogger
            ->expects($this->once())
            ->method('userAutomaticallyLocked')
            ->with(1);

        $sut = new LockedUser($this->mockAdapter, $this->mockSecurityLogger, 1);
        $sut->setIdentity($validIdentity);
        $sut->setCredential($invalidPassword);

        $newResult = $sut->authenticate();

        self::assertEquals(new Result(LockedUser::FAILURE_ACCOUNT_LOCKED, $identity, ['Unsuccessful login attempts exceeded.']), $newResult);
        self::assertEquals(true, $identity->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function test_locked_user_cannot_login()
    {
        $validIdentity = 'test-user@opgtest.com';
        $invalidPassword = 'incorrectPassword';

        $identity = new UserAccount();
        $identity->setId(1);
        $identity->setEmail($validIdentity);
        $identity->setPassword($invalidPassword);
        $identity->setStatus(UserAccount::STATUS_LOCKED);

        $result = new Result(Result::SUCCESS, $identity, []);

        $this->mockAdapter->method('authenticate')->willReturn($result);
        $this->mockAdapter->method('setIdentity')->with($validIdentity);
        $this->mockAdapter->method('setCredential')->with($invalidPassword);

        $sut = new LockedUser($this->mockAdapter, $this->mockSecurityLogger, 3);
        $sut->setIdentity($validIdentity);
        $sut->setCredential($invalidPassword);

        $newResult = $sut->authenticate();

        self::assertEquals(new Result(LockedUser::FAILURE_ACCOUNT_LOCKED, $identity, ['Unsuccessful login attempts exceeded.']), $newResult);
        self::assertEquals(true, $identity->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function test_unknown_user_cannot_login()
    {
        $validIdentity = 'test-user@opgtest.com';
        $invalidPassword = 'incorrectPassword';

        $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, []);

        $this->mockAdapter->method('authenticate')->willReturn($result);
        $this->mockAdapter->method('setIdentity')->with($validIdentity);
        $this->mockAdapter->method('setCredential')->with($invalidPassword);

        $sut = new LockedUser($this->mockAdapter, $this->mockSecurityLogger, 3);
        $sut->setIdentity($validIdentity);
        $sut->setCredential($invalidPassword);

        $newResult = $sut->authenticate();

        $this->assertEquals($result, $newResult);
    }
}
