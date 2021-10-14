<?php

namespace Application\Controller;

use Application\Service\AuthenticationServiceConstructor;
use Application\Service\UserCreationService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * @method Response getResponse()
 */
class UserResendActivationEmailController extends AbstractRestfulController
{
    use BypassMembraneHeader;

    /** @var UserCreationService */
    protected $userCreationService;

    /** @var AuthenticationServiceConstructor */
    protected $authenticationServiceConstructor;

    /**
     * UserResendActivationEmailController constructor.
     * @param AuthenticationServiceConstructor $authenticationServiceConstructor
     * @param UserCreationService $userCreationService
     */
    public function __construct(
        AuthenticationServiceConstructor $authenticationServiceConstructor,
        UserCreationService $userCreationService
    ) {
        $this->authenticationServiceConstructor = $authenticationServiceConstructor;
        $this->userCreationService = $userCreationService;
    }

    public function create($data)
    {
        $userId = $this->params()->fromRoute('id');
        $authenticationService = $this->getAuthenticationService();

        if ($authenticationService->hasIdentity()) {
            if ($authenticationService->getIdentity()->isAdmin()) {
                $this->userCreationService->resendActivationEmail($userId);
            }
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);

            return new JsonModel([]);
        }
        $updateErrors = [
            'status' => Response::STATUS_CODE_401,
            'errors' => [
                'user' => 'Attempting to update user account without authorisation',
            ],
        ];
        $this->getResponse()->setStatusCode($updateErrors['status']);

        return new JsonModel($updateErrors['errors']);
    }

    private function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->hasBypassMembraneHeader()
            ? $this->authenticationServiceConstructor->getBypassMembrane()
            : $this->authenticationServiceConstructor->getNormal();
    }
}
