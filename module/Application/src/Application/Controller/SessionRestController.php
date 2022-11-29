<?php

namespace Application\Controller;

use Application\Service\SecurityLogger;
use Application\Service\UserSessionService;
use InvalidArgumentException;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam;

/**
 * @method BodyParam bodyParam($param = null, $default = null)
 */
class SessionRestController extends AbstractRestfulController
{
    private UserSessionService $userSessionService;
    private LoggerInterface $logger;
    private SecurityLogger $securityLogger;

    public function __construct(
        UserSessionService $userSessionService,
        LoggerInterface $logger,
        SecurityLogger $securityLogger
    ) {
        $this->userSessionService = $userSessionService;
        $this->logger = $logger;
        $this->securityLogger = $securityLogger;
    }

    public function create(mixed $data)
    {
        // Retrieve externally to support content negotiation.
        /** @var array $user */
        $user = $this->bodyParam('user');

        if (!is_array($user) || !isset($user['email']) || !isset($user['password'])) {
            $this->securityLogger->loginFailed('Missing email and/or password.');

            return [
                'error' => 'Missing email and/or password.',
            ];
        }

        $sessionOpenResponse = $this->userSessionService->openUserSession($user['email'], $user['password'], false);
        unset($user);

        /** @var Response $response */
        $response = $this->getResponse();
        $response->setStatusCode($sessionOpenResponse['status']);

        if ($sessionOpenResponse['status'] == 201) {
            $this->securityLogger->loginSuccessful($sessionOpenResponse['body']['userId']);
        } elseif ($sessionOpenResponse['status'] == 401) {
            $this->securityLogger->loginFailed($sessionOpenResponse['body']['error'], $sessionOpenResponse['body']['userId'] ?? null);
            unset($sessionOpenResponse['body']['userId']);
        }

        return $sessionOpenResponse['body'];
    }

    public function delete(string $id)
    {
        /** @var Response $response */
        $response = $this->getResponse();

        try {
            $this->userSessionService->closeUserSession($id, false);
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
