<?php

namespace Application\Controller;

use Application\Service\UserService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * @method Response getResponse()
 */
class UserStatusController extends AbstractRestfulController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function get(mixed $id)
    {
        $userDetails = $this->userService->retrieveUserAccountEntity($id);

        if (!$userDetails instanceof \Application\Model\Entity\UserAccount) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);

            return new JsonModel(['errors' => 'User not found.']);
        }

        return new JsonModel(['status' => $userDetails->getStatus()]);
    }
}
