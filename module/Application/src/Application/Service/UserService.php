<?php

namespace Application\Service;

use Application\Model\Entity\UserAccount;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Laminas\Http\Response;

class UserService extends AbstractService
{
    protected EntityManager $entityManager;

    /**
     * @var array<mixed>
     */
    protected $userServiceConfig;

    /**
     * UserService constructor.
     * @param EntityManager $entityManager
     * @param array<mixed> $userServiceConfig
     */
    public function __construct(EntityManager $entityManager, array $userServiceConfig)
    {
        $this->entityManager = $entityManager;
        $this->userServiceConfig = $userServiceConfig;
    }

    public function getUsers(array $parameters = null)
    {
        /** @var EntityRepository<UserAccount> $userAccountRepository */
        $userAccountRepository = $this->entityManager->getRepository(UserAccount::class);
        $userAccountQueryBuilder = $userAccountRepository->createQueryBuilder('userAccount');
        $userAccountQueryBuilder->orderBy('userAccount.id', 'ASC');
        if (is_array($parameters) && !empty($parameters['email'])) {
            $userAccountQueryBuilder->where('userAccount.email = :email');
            $userAccountQueryBuilder->setParameter(
                ':email',
                $this->canonicalize($parameters['email'])
            );
        }
        $userAccountQueryResult = $userAccountQueryBuilder->getQuery()->getResult();

        return $this->formatUsers($userAccountQueryResult);
    }

    public function verifyPasswordComplexity($proposedPassword)
    {
        $reasons = [];

        if (strlen($proposedPassword) < 8) {
            $reasons[] = 'be 8 characters or more';
        }
        if (!preg_match('/\\d/', $proposedPassword)) {
            $reasons[] = 'include a number';
        }
        if (!preg_match('/[A-Z]/', $proposedPassword)) {
            $reasons[] = 'include a capital letter';
        }

        return $reasons;
    }

    public function setPasswordForUserViaOneTimeToken($oneTimePasswordSetToken, $uid, $password)
    {
        if (!empty($this->verifyPasswordComplexity($password))) {
            return [
                'status' => Response::STATUS_CODE_400,
                'errors' => [
                    'password' => 'Password does not meet complexity requirement',
                ],
            ];
        }

        $userAccount = $this->retrieveUserAccountEntity($uid);
        if (!($userAccount instanceof UserAccount)) {
            return [
                'status' => Response::STATUS_CODE_404,
                'errors' => [
                    'user' => 'User does not exist',
                ],
            ];
        }

        $tokenExpiry = new \DateInterval($this->userServiceConfig['one_time_password_set_lifetime']);
        if (!$userAccount->validateOneTimePasswordSetToken($oneTimePasswordSetToken, $tokenExpiry)) {
            return [
                'status' => Response::STATUS_CODE_401,
                'errors' => [
                    'one-time-password-set-token' => 'One-time password set token is invalid',
                ],
            ];
        }

        // Activate account if it's new.
        if ($userAccount->getStatus() === UserAccount::STATUS_NOT_ACTIVATED) {
            $userAccount->setStatus(UserAccount::STATUS_ACTIVE);
        }
        $userAccount->setPassword($password);
        $userAccount->clearOneTimePasswordSetToken();
        $this->entityManager->persist($userAccount);
        $this->entityManager->flush();

        return [
            'status' => Response::STATUS_CODE_200,
            'errors' => [],
        ];
    }

    public function setPasswordForUserViaExistingPassword(UserAccount $userAccount, $existingPassword, $newPassword)
    {
        $passwordProblems = $this->verifyPasswordComplexity($newPassword);
        if (!empty($passwordProblems)) {
            return [
                'status' => Response::STATUS_CODE_400,
                'errors' => [
                    'password' => 'Password must ' . implode(' and ', $passwordProblems),
                ],
            ];
        }

        // Get new version of UserAccount entity to ensure data consistency.
        $userAccount = $this->retrieveUserAccountEntity($userAccount->getId());
        if (!($userAccount instanceof UserAccount)) {
            return [
                'status' => Response::STATUS_CODE_404,
                'errors' => [
                    'user' => 'User does not exist',
                ],
            ];
        }

        if (UserAccount::verifyPasswordAndStatus($userAccount, $existingPassword)) {
            $userAccount->setPassword($newPassword);
            $this->entityManager->flush();

            return [
                'status' => Response::STATUS_CODE_200,
                'errors' => [],
            ];
        } else {
            return [
                'status' => Response::STATUS_CODE_400,
                'errors' => [
                    'password' => 'Password supplied was incorrect or user is not active',
                ],
            ];
        }
    }

    protected function formatUsers(array $users)
    {
        $formattedUserArray = [];
        foreach ($users as $user) {
            $formattedUserArray[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ];
        }

        return $formattedUserArray;
    }

    public function retrieveUserAccountEntity($id)
    {
        $userAccountRepository = $this->entityManager->getRepository('Application\Model\Entity\UserAccount');

        return $userAccountRepository->find($id);
    }
}
