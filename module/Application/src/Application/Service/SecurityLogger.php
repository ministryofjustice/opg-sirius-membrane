<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Log\Logger;

class SecurityLogger
{
    private const SUBCATEGORY_USER_PASSWORD_CHANGE = 'User password change';
    private const SUBCATEGORY_USER_STATUS_CHANGE = 'User status change';

    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function loginSuccessful(int $userId): void
    {
        $this->logger->info(
            'User login successful',
            [
                'category' => 'Security',
                'subcategory' => 'Authentication',
                'userId' => $userId,
            ]
        );
    }

    public function loginFailed(string $reason, ?int $userId = null): void
    {
        $this->logger->info(
            'User login failed',
            [
                'category' => 'Security',
                'subcategory' => 'Authentication',
                'userId' => $userId,
                'error' => $reason,
            ]
        );
    }

    public function preauthorizedLoginSuccessful(int $userId): void
    {
        $this->logger->info(
            'Preauthorized login successful',
            [
                'category' => 'Security',
                'subcategory' => 'Authentication',
                'userId' => $userId,
            ]
        );
    }

    public function preauthorizedLoginFailed(string $reason): void
    {
        $this->logger->info(
            'Preauthorized login failed',
            [
                'category' => 'Security',
                'subcategory' => 'Authentication',
                'error' => $reason,
            ]
        );
    }

    public function authenticationFailed(): void
    {
        $this->logger->info(
            'User authentication failed',
            [
                'category' => 'Security',
                'subcategory' => 'User authentication',
            ]
        );
    }

    /**
     * @param int|string|null $userId
     */
    public function passwordResetFailed($userId): void
    {
        $this->logger->info(
            'User password reset error',
            [
                'category' => 'Security',
                'subcategory' => 'User password reset',
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param int|string $userId
     */
    public function passwordResetSuccessful($userId): void
    {
        $this->logger->info(
            'User password successfully reset',
            [
                'category' => 'Security',
                'subcategory' => 'User password reset',
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param int|string $userId
     */
    public function passwordUpdateViaSingleUseTokenSuccessful($userId): void
    {
        $this->logger->info(
            'Successful password update via single-use token',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_PASSWORD_CHANGE,
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param int|string $userId
     */
    public function passwordUpdateViaSingleUseTokenFailed($userId, ?string $reason): void
    {
        $params = [
            'category' => 'Security',
            'subcategory' => self::SUBCATEGORY_USER_PASSWORD_CHANGE,
            'userId' => $userId,
        ];

        if ($reason) {
            $params['reason'] = $reason;
        }

        $this->logger->info('Unsuccessful password update via single-use token', $params);
    }

    /**
     * @param int|string $userId
     */
    public function passwordUpdateViaSuppliedPasswordSuccessful($userId): void
    {
        $this->logger->info(
            'Successful password update via supplied password',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_PASSWORD_CHANGE,
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param int|string $userId
     * @param mixed $errors
     */
    public function passwordUpdateViaSuppliedPasswordFailed($userId, $errors): void
    {
        $this->logger->info(
            'Unsuccessful password update via supplied password',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_PASSWORD_CHANGE,
                'userId' => $userId,
                'error' => json_encode($errors),
            ]
        );
    }

    /**
     * @param mixed $userId
     */
    public function userLocked($userId): void
    {
        $this->logger->info(
            'User account locked',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_STATUS_CHANGE,
                'userId' => $userId,
            ]
        );
    }

    public function userAutomaticallyLocked(int $userId): void
    {
        $this->logger->info(
            'User account automatically locked',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_STATUS_CHANGE,
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param mixed $userId
     */
    public function userSuspended($userId): void
    {
        $this->logger->info(
            'User account suspended',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_STATUS_CHANGE,
                'userId' => $userId,
            ]
        );
    }

    /**
     * @param mixed $userId
     */
    public function userActivated($userId): void
    {
        $this->logger->info(
            'User account activated',
            [
                'category' => 'Security',
                'subcategory' => self::SUBCATEGORY_USER_STATUS_CHANGE,
                'userId' => $userId,
            ]
        );
    }
}
