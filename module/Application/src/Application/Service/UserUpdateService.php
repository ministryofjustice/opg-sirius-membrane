<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\Entity\UserAccount;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Response;

class UserUpdateService
{
    protected EntityManager $entityManager;
    private SecurityLogger $securityLogger;

    public function __construct(EntityManager $entityManager, SecurityLogger $securityLogger)
    {
        $this->entityManager = $entityManager;
        $this->securityLogger = $securityLogger;
    }

    /**
     * @param array<mixed> $newValues
     * @param int $userId
     */
    public function updateUser($userId, $newValues)
    {
        $userAccountRepository = $this->entityManager->getRepository('Application\Model\Entity\UserAccount');

        /** @var UserAccount $userAccountEntity */
        $userAccountEntity = $userAccountRepository->find($userId);

        if (empty($userAccountEntity)) {
            return [
                'status' => Response::STATUS_CODE_404,
                'body' => [],
            ];
        }

        if (isset($newValues['status'])) {
            if ($newValues['status'] !== $userAccountEntity->getStatus()) {
                switch ($newValues['status']) {
                    case UserAccount::STATUS_LOCKED:
                        $this->securityLogger->userLocked($userId);
                        break;
                    case UserAccount::STATUS_SUSPENDED:
                        $this->securityLogger->userSuspended($userId);
                        break;
                    case UserAccount::STATUS_ACTIVE:
                        $this->securityLogger->userActivated($userId);
                        break;
                    default:
                        break;
                }
            }

            if (
                    $newValues['status'] == UserAccount::STATUS_ACTIVE
                 && $userAccountEntity->getStatus() != UserAccount::STATUS_ACTIVE
            ) {
                $userAccountEntity->resetUnsuccessfulLoginAttempts();
            }
        }

        if (isset($newValues['email'])) {
            $userAccountEntity->setEmail(strtolower($newValues['email']));
        }

        $userAccountEntity->setStatus($newValues['status']);

        $userAccountEntity->setAdmin(
            array_key_exists('roles', $newValues) &&
            in_array('System Admin', $newValues['roles'], true)
        );

        $this->entityManager->flush();

        return [
            'status' => Response::STATUS_CODE_200,
            'body' => [],
        ];
    }

    public function deleteUser(int $userId): void
    {
        $userAccountRepository = $this->entityManager->getRepository('Application\Model\Entity\UserAccount');

        /** @var UserAccount $user */
        $user = $userAccountRepository->find($userId);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
