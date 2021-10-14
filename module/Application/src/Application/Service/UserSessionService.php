<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Authentication\Adapter\LockedUser;
use Application\Model\Entity\UserAccount;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineModule\Authentication\Adapter\ObjectRepository;
use InvalidArgumentException;
use JwtLaminasAuth\Authentication\Storage\JwtStorage;
use JwtLaminasAuth\Service\JwtService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Response;
use Laminas\Session\SessionManager;

class UserSessionService extends AbstractService
{
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var AuthenticationServiceConstructor
     */
    private $authenticationServiceConstructor;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JwtService
     */
    private $jwt;

    /**
     * @var int
     */
    private $jwtExpiry;

    public function __construct(
        AuthenticationServiceConstructor $authenticationServiceConstructor,
        SessionManager $sessionManager,
        EntityManagerInterface $entityManager,
        int $jwtExpiry,
        JwtService $jwt
    ) {
        $this->authenticationServiceConstructor = $authenticationServiceConstructor;
        $this->sessionManager = $sessionManager;
        $this->entityManager = $entityManager;
        $this->jwtExpiry = $jwtExpiry;
        $this->jwt = $jwt;
    }

    public function openUserSession(string $email, string $password, bool $returnJwt): array
    {
        $email = $this->canonicalize($email);

        /** @var ObjectRepository $adapter */
        $adapter = $this->getAuthenticationService($returnJwt)->getAdapter();
        $adapter->setIdentity($email);
        $adapter->setCredential($password);

        $result = $this->getAuthenticationService($returnJwt)->authenticate();
        $response = [
            'status' => Response::STATUS_CODE_401,
            'body' => [
                'error' => 'Invalid email or password.',
            ],
        ];

        /** @var UserAccount $userAccount */
        $userAccount = $result->getIdentity();

        if ($result->isValid()) {
            $userAccount->setLastLoggedInValue();
            $this->entityManager->persist($userAccount);

            $response = [
                'status' => Response::STATUS_CODE_201,
                'body' => [
                    'email' => $email,
                    'userId' => $userAccount->getId(),
                ],
            ];
            $response['body']['authentication_token'] = $this->sessionManager->getId();

            if ($returnJwt) {
                $response['body']['jwt'] = $this->jwt->createSignedToken(
                    JwtStorage::SESSION_CLAIM_NAME,
                    $email,
                    $this->jwtExpiry
                )->toString();
            }
        } elseif ($result->getCode() === LockedUser::FAILURE_ACCOUNT_LOCKED) {
            $response = [
                'status' => Response::STATUS_CODE_403,
                'body' => [
                    'userId' => $userAccount->getId(),
                    'error' => 'Unsuccessful login attempts exceeded.',
                    'locked' => true,
                ],
            ];
        }

        $this->entityManager->flush();

        return $response;
    }

    public function getSessionId(): string
    {
        return $this->sessionManager->getId();
    }

    public function closeUserSession(string $sessionId, bool $returnJwt): void
    {
        // Check that the user is logged in and is deleting their own, current session.
        if (!$this->getAuthenticationService($returnJwt)->hasIdentity()) {
            throw new InvalidArgumentException("User is not logged in");
        }

        if ($this->sessionManager->getId() !== $sessionId) {
            throw new InvalidArgumentException(sprintf(
                "User session id ('%s') does not match the given session id ('%s')",
                $this->sessionManager->getId(),
                $sessionId
            ));
        }

        // Log the user out.
        $this->getAuthenticationService($returnJwt)->clearIdentity();
    }

    private function getAuthenticationService(bool $returnJwt): AuthenticationService
    {
        return $returnJwt
            ? $this->authenticationServiceConstructor->getBypassMembrane()
            : $this->authenticationServiceConstructor->getNormal();
    }
}
