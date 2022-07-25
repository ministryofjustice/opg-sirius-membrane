<?php

namespace Application\Controller;

use Application\Proxy\ApplicationProxy;
use Application\Service\AuthenticationServiceConstructor;
use Application\Service\RequestService;
use Application\Service\SecurityLogger;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * Class AuthController
 */
class AuthController extends AbstractActionController
{
    use BypassMembraneHeader;

    public function __construct(
        private readonly RequestService $requestService,
        private readonly AuthenticationServiceConstructor $authenticationService,
        private readonly ApplicationProxy $applicationProxy,
        private readonly LoggerInterface $logger,
        private readonly SecurityLogger $securityLogger
    ) {
    }

    private function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->hasBypassMembraneHeader()
            ? $this->authenticationService->getBypassMembrane()
            : $this->authenticationService->getNormal();
    }

    /**
     * @see AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var Request $request */
        $request = $this->getRequest();

        $this->requestService->setRequest($request);

        if ($this->requestService->areInvalidHeadersPresent()) {
            $this->logger->err(
                'Request headers invalid',
                [
                    'category' => 'Security',
                    'subcategory' => 'User authentication',
                    'request' => json_encode($request),
                ]
            );
            $response->setStatusCode(Response::STATUS_CODE_401);

            return $response;
        }

        $authService = $this->getAuthenticationService();

        if ($authService->hasIdentity()) {
            $userEmail = $authService->getIdentity()->getEmail();

            if (!empty($userEmail)) {
                $this->requestService->updateHeadersWithUserId($userEmail);

                return $this->applicationProxy->send(
                    $this->requestService->getRequest()
                );
            }
        } else {
            $this->securityLogger->authenticationFailed();
            $response->setStatusCode(Response::STATUS_CODE_401);
        }

        return $response;
    }
}
