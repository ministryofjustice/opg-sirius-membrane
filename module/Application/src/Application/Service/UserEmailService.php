<?php

namespace Application\Service;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\ApiException;
use Application\Model\Entity\UserAccount;
use Laminas\Log\LoggerInterface;

class UserEmailService
{
    /**
     * @var array
     */
    protected $userServiceConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    private NotifyClient $notifyClient;

    const TEMPLATE_ACTIVATION = 'd89f2417-f86b-4411-a050-2d45efa71978';
    const TEMPLATE_PASSWORD_RESET = 'a8d65fee-8ca0-4d7c-856f-9b2fe665b965';

    /**
     * @param array<mixed> $userServiceConfig
     */
    public function __construct(array $userServiceConfig, NotifyClient $notifyClient, LoggerInterface $logger)
    {
        $this->userServiceConfig = $userServiceConfig;
        $this->notifyClient = $notifyClient;
        $this->logger = $logger;
    }

    public function sendActivationEmail(UserAccount $newUser)
    {
        $oneTimePasswordSetLink = $this->userServiceConfig['email_system_base_url'] . '/auth/activation/' . $newUser->getId() . '/' . $newUser->getOneTimePasswordSetToken();

        try {
            $this->notifyClient->sendEmail(
                $newUser->getEmail(),
                self::TEMPLATE_ACTIVATION,
                [
                    'username' => $newUser->getEmail(),
                    'oneTimePasswordSetLink'  => $oneTimePasswordSetLink
                ],
            );

            $this->logger->info(
                'Activation email has been successfully sent to user ' . $newUser->getEmail(),
                ['category' => 'Email']
            );
        } catch (ApiException $e) {
            $this->logger->err(
                'An exception occurred when sending activation email. Message: ' . $e->getMessage(),
                ['category' => 'Email', 'stackTrace' => debug_backtrace()]
            );
        }
    }

    public function sendPasswordResetEmail(UserAccount $newUser)
    {
        $oneTimePasswordSetLink
            = $this->userServiceConfig['email_system_base_url']
            . '/auth/reset-password/' . $newUser->getId() . '/'
            . $newUser->getOneTimePasswordSetToken();

        try {
            $this->notifyClient->sendEmail(
                $newUser->getEmail(),
                self::TEMPLATE_PASSWORD_RESET,
                [
                    'username' => $newUser->getEmail(),
                    'oneTimePasswordSetLink'  => $oneTimePasswordSetLink
                ],
            );

            $this->logger->info(
                'Password reset email has been successfully sent to user ' . $newUser->getEmail(),
                ['category' => 'Email']
            );
        } catch (ApiException $e) {
            $this->logger->err(
                'An exception occurred when sending password reset email. Message: ' . $e->getMessage(),
                ['category' => 'Email', 'stackTrace' => debug_backtrace()]
            );
        }
    }
}
