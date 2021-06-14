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

    private RequestService $requestService;
    private AuthenticationServiceConstructor $authenticationService;
    private ApplicationProxy $applicationProxy;
    private LoggerInterface $logger;
    private SecurityLogger $securityLogger;

    public function __construct(
        RequestService $requestService,
        AuthenticationServiceConstructor $authenticationService,
        ApplicationProxy $applicationProxy,
        LoggerInterface $logger,
        SecurityLogger $securityLogger
    ) {
        $this->requestService = $requestService;
        $this->authenticationService = $authenticationService;
        $this->applicationProxy = $applicationProxy;
        $this->logger = $logger;
        $this->securityLogger = $securityLogger;
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

        $authenticationService = $this->getAuthenticationService();

        if ($authenticationService->hasIdentity()) {
            $userEmail = $authenticationService->getIdentity()->getEmail();

            if (!empty($userEmail)) {
                $this->requestService->updateHeadersWithUserId($userEmail);

                return $this->applicationProxy->send(
                    $this->requestService->getRequest()
                );
            }
        } else {
            $this->securityLogger->authenticationFailed();
            $response->setStatusCode(Response::STATUS_CODE_401);

            return $response;
        }
    }
}
