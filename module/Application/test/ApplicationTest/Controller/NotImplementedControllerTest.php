<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Laminas\Http\Response;

class NotImplementedControllerTest extends BaseControllerTestCase
{
    public function testNotImplementedURLReturns()
    {
        // Dispatch request to old Devise URL, expected 410 GONE response.
        $this->dispatch('/auth/users/password/id');
        $this->assertResponseStatusCode(Response::STATUS_CODE_410);
        $this->assertControllerName('Application\Controller\NotImplementedController');
        $this->assertMatchedRouteName('devise-set-password-service');
    }
}
