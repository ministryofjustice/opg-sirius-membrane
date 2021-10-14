<?php

namespace Application\Service;

use Application\Exception\UserAlreadyExistsException;
use Application\Model\Entity\UserAccount;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;

class UserCreationService extends AbstractService
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
     * UserCreationService constructor.
     * @param EntityManager $entityManager
     * @param UserEmailService $userEmailService
     */
    public function __construct(EntityManager $entityManager, UserEmailService $userEmailService)
    {
        $this->entityManager = $entityManager;
        $this->userEmailService = $userEmailService;
    }

    public function createUser($email, $password = null, $isAdmin = false)
    {
        $newUser = new UserAccount();
        $newUser->setEmail($this->canonicalize($email));
        $newUser->setAdmin($isAdmin);

        // If password is not specified, user must activate via email.
        if (empty($password)) {
            $newUser->setStatus(UserAccount::STATUS_NOT_ACTIVATED);
            $newUser->setOneTimePasswordSetToken();
        } else {
            $newUser->setStatus(UserAccount::STATUS_ACTIVE);
            $newUser->setPassword($password);
        }

        try {
            $this->entityManager->persist($newUser);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new UserAlreadyExistsException('User already exists');
        }

        // We must send the activation email after persisting, so we know the User ID.
        if ($newUser->getStatus() === UserAccount::STATUS_NOT_ACTIVATED) {
            $this->userEmailService->sendActivationEmail($newUser);
        }

        return $newUser;
    }

    public function resendActivationEmail($id)
    {
        $userAccountRepository = $this->entityManager->getRepository('Application\Model\Entity\UserAccount');
        $userAccount = $userAccountRepository->find($id);

        $userAccount->setStatus(UserAccount::STATUS_NOT_ACTIVATED);
        $userAccount->setOneTimePasswordSetToken();

        $this->entityManager->flush();

        $this->userEmailService->sendActivationEmail($userAccount);
    }
}
