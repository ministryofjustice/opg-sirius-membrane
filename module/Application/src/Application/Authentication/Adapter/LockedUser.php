<?php

namespace Application\Authentication\Adapter;

use Application\Model\Entity\UserAccount;
use Application\Service\SecurityLogger;
use Laminas\Authentication\Result;

class LockedUser implements ChainingAdapterInterface
{
    public const FAILURE_ACCOUNT_LOCKED = -127;

    private ChainingAdapterInterface $wrapped;
    private int $maxLoginAttempts;
    private SecurityLogger $securityLogger;

    public function __construct(ChainingAdapterInterface $wrappedAdapter, SecurityLogger $securityLogger, int $maxLoginAttempts)
    {
        $this->wrapped = $wrappedAdapter;
        $this->securityLogger = $securityLogger;
        $this->maxLoginAttempts = $maxLoginAttempts;
    }

    public function setIdentity($identity)
    {
        $this->wrapped->setIdentity($identity);
    }

    public function setCredential($credential)
    {
        $this->wrapped->setCredential($credential);
    }

    public function authenticate()
    {
        $result = $this->wrapped->authenticate();
        /** @var UserAccount $identity */
        $identity = $result->getIdentity();

        if ($identity instanceof UserAccount) {
            if ($identity->isLocked()) {
                $result = new Result(self::FAILURE_ACCOUNT_LOCKED, $identity, ['Unsuccessful login attempts exceeded.']);
            }

            if (!$result->isValid()) {
                $identity->incrementUnsuccessfulLoginAttempts();

                if ($identity->validateUserHasExceededUnsuccessfulLoginAttempts($this->maxLoginAttempts)) {
                    $identity->setStatus(UserAccount::STATUS_LOCKED);
                    $result = new Result(self::FAILURE_ACCOUNT_LOCKED, $identity, ['Unsuccessful login attempts exceeded.']);
                    $this->securityLogger->userAutomaticallyLocked($identity->getId());
                }
            }
        }

        return $result;
    }
}
