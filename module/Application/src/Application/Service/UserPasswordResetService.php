<?php

namespace Application\Service;

use Application\Model\Entity\UserAccount;
use Doctrine\ORM\EntityManager;

class UserPasswordResetService
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UserEmailService
     */
    protected $userEmailService;

    /**
     * UserPasswordResetService constructor.
     * @param EntityManager $entityManager
     * @param UserEmailService $userEmailService
     */
    public function __construct(EntityManager $entityManager, UserEmailService $userEmailService)
    {
        $this->entityManager = $entityManager;
        $this->userEmailService = $userEmailService;
    }

    public function sendPasswordResetViaEmail($userID)
    {
        $userAccountRepository = $this->entityManager->getRepository('Application\Model\Entity\UserAccount');

        /** @var UserAccount $userAccount */
        $userAccount = $userAccountRepository->find($userID);

        if ($userAccount instanceof UserAccount) {
            $userAccount->setOneTimePasswordSetToken();
            $this->entityManager->persist($userAccount);
            $this->entityManager->flush();
            $this->userEmailService->sendPasswordResetEmail($userAccount);

            return [
                'errors' => [],
            ];
        } else {
            return [
                'errors' => [
                    'user' => 'User does not exist',
                ],
            ];
        }
    }
}
