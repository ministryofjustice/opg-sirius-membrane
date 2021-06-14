<?php

namespace Application\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * @method Response getResponse()
 */
class NotImplementedController extends AbstractActionController
{
    /**
     * @see AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_410);

        return $response;
    }
}
