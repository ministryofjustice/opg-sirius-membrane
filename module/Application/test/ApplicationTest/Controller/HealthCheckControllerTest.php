<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use PHPUnit\Framework\Assert;

class HealthCheckControllerTest extends BaseControllerTestCase
{
    /**
     * @group functional
     */
    public function testHealthCheckIndexSucceeds()
    {
        $this->dispatch('/auth/health-check');

        $this->assertResponseStatusCode(200);
    }

    /**
     * @group functional
     */
    public function testHealthCheckDbVersionSucceeds()
    {
        $this->dispatch('/auth/health-check/db-version');

        $this->assertResponseStatusCode(200);
        $this->assertMatchesRegularExpression('/back-end-db-migration-version/', $this->getResponse()->getContent());
    }

    /**
     * @group functional
     */
    public function testHealthCheckServiceStatusSucceeds()
    {
        $this->dispatch('/auth/health-check/service-status');

        $this->assertResponseStatusCode(200);
        $result = json_decode($this->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('ok', $result);
        Assert::assertIsBool($result['ok']);
        Assert::assertArrayHasKey('ddc-queue', $result);
        Assert::assertArrayHasKey('queue-type', $result['ddc-queue']);
        Assert::assertTrue($result['ddc-queue']['ok']);
        Assert::assertArrayHasKey('attributes', $result['ddc-queue']);
    }
}
