<?php

namespace ApplicationTest\Service;

use Laminas\Log\Logger;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Application\Model\Entity\UserAccount;
use Application\Service\UserEmailService;
use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\ApiException;

class UserEmailServiceTest extends TestCase
{
    protected $userServiceConfig;

    protected $mockUserAccount;

    protected $mockNotifyClient;

    protected $mockLogger;

    protected ApiException $apiException;

    public function setup(): void
    {
        parent::setUp();

        $this->mockUserAccount = $this->createMock(UserAccount::class);

        $this->userServiceConfig = [
            'email_system_base_url' => 'http://samplebaseurl',
        ];

        $this->mockNotifyClient = $this->createMock(NotifyClient::class);

        $this->mockLogger = $this->createMock(Logger::class);

        $this->apiException = new ApiException('failure', 0, ['errors' => [
            ['error' => 'template', 'message' => 'template not found']
        ]], new Response());
    }

    public function testActivationEmail()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(6));

        $this->mockUserAccount->expects($this->exactly(3))
            ->method('getEmail')
            ->will($this->returnValue('test@example.com'));

        $this->mockUserAccount->expects($this->once())
            ->method('getOneTimePasswordSetToken')
            ->will($this->returnValue('sampleOneTimePasswordSetToken'));

        $this->mockUserAccount->expects($this->once())
            ->method('getId');

        $this->mockNotifyClient->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                UserEmailService::TEMPLATE_ACTIVATION,
                [
                    'username' => 'test@example.com',
                    'oneTimePasswordSetLink'  => 'http://samplebaseurl/auth/activation/6/sampleOneTimePasswordSetToken'
                ]
            );

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                "Activation email has been successfully sent to user test@example.com",
                ['category' => 'Email']
            );

        $userEmailService = new UserEmailService($this->userServiceConfig, $this->mockNotifyClient, $this->mockLogger);
        $userEmailService->sendActivationEmail($this->mockUserAccount);
    }

    public function testActivationEmailException()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(6));

        $this->mockUserAccount->expects($this->exactly(2))
            ->method('getEmail')
            ->will($this->returnValue('test@example.com'));

        $this->mockUserAccount->expects($this->once())
            ->method('getOneTimePasswordSetToken')
            ->will($this->returnValue('sampleOneTimePasswordSetToken'));

        $this->mockUserAccount->expects($this->once())
            ->method('getId');

        $this->mockNotifyClient->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                UserEmailService::TEMPLATE_ACTIVATION,
                [
                    'username' => 'test@example.com',
                    'oneTimePasswordSetLink'  => 'http://samplebaseurl/auth/activation/6/sampleOneTimePasswordSetToken'
                ]
            )
            ->willThrowException($this->apiException);

        $this->mockLogger->expects($this->once())
            ->method('err')
            ->with(
                'An exception occurred when sending activation email. Message: template: "template not found"',
                $this->anything(),
            );

        $userEmailService = new UserEmailService($this->userServiceConfig, $this->mockNotifyClient, $this->mockLogger);
        $userEmailService->sendActivationEmail($this->mockUserAccount);
    }

    public function testPasswordResetEmail()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(6));

        $this->mockUserAccount->expects($this->exactly(3))
            ->method('getEmail')
            ->will($this->returnValue('test@example.com'));

        $this->mockUserAccount->expects($this->once())
            ->method('getOneTimePasswordSetToken')
            ->will($this->returnValue('sampleOneTimePasswordSetToken'));

        $this->mockUserAccount->expects($this->once())
            ->method('getId');

        $this->mockNotifyClient->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                UserEmailService::TEMPLATE_PASSWORD_RESET,
                [
                    'username' => 'test@example.com',
                    'oneTimePasswordSetLink'  => 'http://samplebaseurl/auth/reset-password/6/sampleOneTimePasswordSetToken'
                ]
            );

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                "Password reset email has been successfully sent to user test@example.com",
                ['category' => 'Email']
            );

        $userEmailService = new UserEmailService($this->userServiceConfig, $this->mockNotifyClient, $this->mockLogger);
        $userEmailService->sendPasswordResetEmail($this->mockUserAccount);
    }

    public function testPasswordResetEmailException()
    {
        $this->mockUserAccount->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(6));

        $this->mockUserAccount->expects($this->exactly(2))
            ->method('getEmail')
            ->will($this->returnValue('test@example.com'));

        $this->mockUserAccount->expects($this->once())
            ->method('getOneTimePasswordSetToken')
            ->will($this->returnValue('sampleOneTimePasswordSetToken'));

        $this->mockUserAccount->expects($this->once())
            ->method('getId');

        $this->mockNotifyClient->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                UserEmailService::TEMPLATE_PASSWORD_RESET,
                [
                    'username' => 'test@example.com',
                    'oneTimePasswordSetLink'  => 'http://samplebaseurl/auth/reset-password/6/sampleOneTimePasswordSetToken'
                ]
            )
            ->willThrowException($this->apiException);

        $this->mockLogger->expects($this->once())
            ->method('err')
            ->with(
                'An exception occurred when sending password reset email. Message: template: "template not found"',
                $this->anything(),
            );

        $userEmailService = new UserEmailService($this->userServiceConfig, $this->mockNotifyClient, $this->mockLogger);
        $userEmailService->sendPasswordResetEmail($this->mockUserAccount);
    }
}
