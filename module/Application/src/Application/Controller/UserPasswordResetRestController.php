<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\SecurityLogger;
use Application\Service\UserPasswordResetService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * @method Response getResponse()
 */
class UserPasswordResetRestController extends AbstractRestfulController
{
    protected UserPasswordResetService $userPasswordResetService;
    private SecurityLogger $securityLogger;

    public function __construct(
        UserPasswordResetService $userPasswordResetService,
        SecurityLogger $securityLogger
    ) {
        $this->userPasswordResetService = $userPasswordResetService;
        $this->securityLogger = $securityLogger;
    }

    public function create(mixed $data)
    {
        $userId = $this->params()->fromRoute('id');
        $passwordResetErrors = $this->userPasswordResetService->sendPasswordResetViaEmail($userId);

        if (isset($passwordResetErrors['errors']['user'])) {
            $this->securityLogger->passwordResetFailed($userId);
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);

            return new JsonModel($passwordResetErrors);
        }

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
        $this->securityLogger->passwordResetSuccessful($userId);

        return new JsonModel($passwordResetErrors);
    }
}
