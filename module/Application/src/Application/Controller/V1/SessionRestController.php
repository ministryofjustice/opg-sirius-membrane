<?php

declare(strict_types=1);

namespace Application\Controller\V1;

use Application\Model\Entity\UserAccount;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\SecurityLogger;
use Application\Service\UserSessionService;
use InvalidArgumentException;
use Laminas\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * @method BodyParam bodyParam($param = null, $default = null)
 */
class SessionRestController extends AbstractRestfulController
{
    public function __construct(
        private readonly AuthenticationServiceConstructor $authenticationServiceConstructor,
        private readonly LoggerInterface $logger,
        private readonly SecurityLogger $securityLogger,
        private readonly UserSessionService $userSessionService,
    ) {
    }

    public function create($data)
    {
        // Retrieve externally to support content negotiation.
        /** @var array $user */
        $user = $this->bodyParam('user');

        /** @var Response $response */
        $response = $this->getResponse();

        /** @var mixed $preauthorized */
        $preauthorized = $this->bodyParam('preauthorized');

        if ($preauthorized === true) {
            $authService = $this->authenticationServiceConstructor->getBypassMembrane();

            if (!$authService->hasIdentity() || $authService->getIdentity() === null) {
                $this->securityLogger->preauthorizedLoginFailed('Could not verify token');

                $response->setStatusCode(401);
                return new JsonModel([
                    'error' => 'Could not verify token',
                ]);
            }

            $user = $authService->getIdentity();

            if ($user->getStatus() === UserAccount::STATUS_LOCKED || $user->getStatus() === UserAccount::STATUS_SUSPENDED) {
                $this->securityLogger->preauthorizedLoginFailed(sprintf('Account is %s', $user->getStatus()));

                $response->setStatusCode(403);
                return new JsonModel([
                    'status' => $user->getStatus(),
                ]);
            }

            $this->securityLogger->preauthorizedLoginSuccessful($user->getId());
            $response->setStatusCode(201);

            return new JsonModel([
                'email' => $user->getEmail(),
                'authentication_token' => $this->userSessionService->getSessionId(),
            ]);
        }

        if (!is_array($user) || !isset($user['email']) || !isset($user['password'])) {
            $this->securityLogger->loginFailed('Missing email and/or password.');

            return [
                'error' => 'Missing email and/or password.',
            ];
        }

        $sessionOpenResponse = $this->userSessionService->openUserSession($user['email'], $user['password'], true);
        unset($user);

        $response->setStatusCode($sessionOpenResponse['status']);

        if ($sessionOpenResponse['status'] == 201) {
            $this->securityLogger->loginSuccessful($sessionOpenResponse['body']['userId']);
        } elseif ($sessionOpenResponse['status'] == 401) {
            $this->securityLogger->loginFailed($sessionOpenResponse['body']['error'], $sessionOpenResponse['body']['userId'] ?? null);
            unset($sessionOpenResponse['body']['userId']);
        }

        return new JsonModel($sessionOpenResponse['body']);
    }

    public function delete($id)
    {
        /** @var Response $response */
        $response = $this->getResponse();

        try {
            $this->userSessionService->closeUserSession($id, true);
            $response->setStatusCode(Response::STATUS_CODE_204);
            $content = null;

            $this->logger->info(
                'User logout successful',
                [
                    'category' => 'Security',
                    'subcategory' => 'Authentication',
                    'userId' => $id,
                ]
            );
        } catch (InvalidArgumentException $e) {
            $response->setStatusCode(Response::STATUS_CODE_401);
            $content = ['error' => $e->getMessage()];

            $this->logger->info(
                'User logout failed',
                [
                    'category' => 'Security',
                    'subcategory' => 'Authentication',
                    'userId' => $id,
                    'error' => $e->getMessage(),
                ]
            );
        }

        return new JsonModel($content);
    }
}
