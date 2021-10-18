<?php
declare(strict_types=1);
namespace ApplicationTest\Model\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Application\Model\Entity\UserAccount;

class UserAccountTest extends TestCase
{
    /** @var UserAccount $userAccount */
    protected $userAccount;

    public function setup(): void
    {
        $this->userAccount = new UserAccount();
    }

    public function testFluentSetEmail()
    {
        $userAccount = $this->userAccount->setEmail('test@test.com');
        $this->assertSame($userAccount, $this->userAccount, 'setEmail failed to return the user account fluently');

        $email = $this->userAccount->getEmail();
        $this->assertEquals('test@test.com', $email, 'Email address in setter is different to that returned by getter');
    }

    public function testFluentSetAdmin()
    {
        $userAccount = $this->userAccount->setAdmin(true);
        $this->assertSame($userAccount, $this->userAccount, 'setAdmin failed to return the user account fluently');

        $isAdmin = $this->userAccount->isAdmin();
        $this->assertTrue($isAdmin, 'Admin status in setter is different to that returned by getter');
    }

    public function testFluentSetId()
    {
        $userAccount = $this->userAccount->setId(23);
        $this->assertSame($userAccount, $this->userAccount, 'setId failed to return the user account fluently');

        $userId = $this->userAccount->getId();
        $this->assertEquals(23, $userId, 'Id in setter is different to that returned by getter');
    }

    public function testFluentSetPassword()
    {
        $userAccount = $this->userAccount->setPassword('Password1');
        $this->assertSame($userAccount, $this->userAccount, 'setPassword failed to return the user account fluently');
    }

    public function testFluentSetValidStatus()
    {
        $userAccount = $this->userAccount->setStatus(UserAccount::STATUS_ACTIVE);
        $this->assertSame($userAccount, $this->userAccount, 'setStatus failed to return the user account fluently');
    }

    public function testFluentSetInvalidStatus()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attempting to set UserAccount status to invalid status invalid status');
        $this->userAccount->setStatus('invalid status');
    }

    public function testFluentSetOneTimePasswordSetToken()
    {
        $userAccount = $this->userAccount->setOneTimePasswordSetToken();
        $this->assertSame($userAccount, $this->userAccount, 'setOneTimePasswordSetToken failed to return the user account fluently');
    }

    public function testOneTimePasswordSetTokenIsReset()
    {
        $firstToken = $this->userAccount->setOneTimePasswordSetToken()->getOneTimePasswordSetToken();
        $secondToken = $this->userAccount->setOneTimePasswordSetToken()->getOneTimePasswordSetToken();
        $this->assertNotEquals($firstToken, $secondToken, 'setOneTimePasswordSetToken failed to reset one-time password token');
    }

    public function testOneTimePasswordTokenValidation()
    {
        $token = $this->userAccount->setOneTimePasswordSetToken()->getOneTimePasswordSetToken();
        $valid = $this->userAccount->validateOneTimePasswordSetToken($token, new \DateInterval('PT1H'));
        $this->assertTrue($valid, 'validateOneTimePasswordSetToken is not validating a valid token');
    }

    public function testOneTimePasswordTokenValidationExpiry()
    {
        $token = $this->userAccount->setOneTimePasswordSetToken()->getOneTimePasswordSetToken();

        // Sleep necessary to ensure the 1 second expiry period had completed expired.
        // DateTime comparisons are only accurate down to the second.
        sleep(2);

        $valid = $this->userAccount->validateOneTimePasswordSetToken($token, new \DateInterval('PT1S'));
        $this->assertFalse($valid, 'validateOneTimePasswordSetToken is validating an expired token');
    }

    public function testOneTimePasswordTokenValidationNotSet()
    {
        $valid = $this->userAccount->validateOneTimePasswordSetToken('placeholderToken', new \DateInterval('PT1S'));
        $this->assertFalse($valid, 'validateOneTimePasswordSetToken is validating when the token had not been set');
    }

    public function testOneTimePasswordTokenValidationIsCleared()
    {
        $this->userAccount->setOneTimePasswordSetToken();
        $this->userAccount->clearOneTimePasswordSetToken();
        $token = $this->userAccount->getOneTimePasswordSetToken();
        $this->assertEmpty($token, 'clearOneTimePasswordSetToken is not clearing token');
    }

    public function testPasswordHashing()
    {
        $this->userAccount->setPassword('Password1');

        $validPassword = password_verify('Password1', $this->userAccount->getPassword());

        $this->assertTrue($validPassword);
    }

    public function testVerifyPasswordAndStatusActive()
    {
        $this->userAccount->setPassword('Password1');
        $this->userAccount->setStatus(UserAccount::STATUS_ACTIVE);

        $validPassword = UserAccount::verifyPasswordAndStatus($this->userAccount, 'Password1');

        $this->assertTrue($validPassword);
    }

    public function testVerifyPasswordAndStatusNegativeCaseIncorrectPassword()
    {
        $this->userAccount->setPassword('Password1');
        $this->userAccount->setStatus(UserAccount::STATUS_ACTIVE);

        $validPassword = UserAccount::verifyPasswordAndStatus($this->userAccount, 'Password2');

        $this->assertFalse($validPassword);
    }

    public function testIncrementUnsuccesfulLoginAttemptsFromNull()
    {
        $this->userAccount->incrementUnsuccessfulLoginAttempts();
        $this->assertTrue($this->userAccount->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function testIncrementUnsuccesfulLoginAttemptsFromZero()
    {
        $this->userAccount->resetUnsuccessfulLoginAttempts();
        $this->userAccount->incrementUnsuccessfulLoginAttempts();
        $this->assertTrue($this->userAccount->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function testValidateUserHasNotExceededUnsuccessfulLoginAttemptsNull()
    {
        $this->assertFalse($this->userAccount->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function testValidateUserHasNotExceededUnsuccessfulLoginAttemptsZero()
    {
        $this->userAccount->resetUnsuccessfulLoginAttempts();
        $this->assertFalse($this->userAccount->validateUserHasExceededUnsuccessfulLoginAttempts(1));
    }

    public function testValidateUserHasNotExceededUnsuccessfulLoginAttemptsLimitThree()
    {
        $this->userAccount->incrementUnsuccessfulLoginAttempts();
        $this->userAccount->incrementUnsuccessfulLoginAttempts();
        $this->userAccount->incrementUnsuccessfulLoginAttempts();
        $this->assertTrue($this->userAccount->validateUserHasExceededUnsuccessfulLoginAttempts(3));
    }

    /** @dataProvider provideIsLocked */
    public function testIsLocked(string $status, bool $locked)
    {
        $sut = new UserAccount();
        $sut->setStatus($status);
        self::assertEquals($locked, $sut->isLocked());
    }

    public function provideIsLocked()
    {
        return [
            [UserAccount::STATUS_ACTIVE, false],
            [UserAccount::STATUS_LOCKED, true],
            [UserAccount::STATUS_NOT_ACTIVATED, true],
            [UserAccount::STATUS_SUSPENDED, true],
        ];
    }
}
