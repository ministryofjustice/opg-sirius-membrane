<?php

namespace ApplicationTest\Service;

use Application\Proxy\ApplicationProxy;
use Application\Service\ServiceStatusService;
use Exception;
use PHPUnit\Framework\MockObject\MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Laminas\Http\Response;
use Laminas\Log\LoggerInterface;

class ServiceStatusServiceTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject $mockLogger
     */
    private $mockLogger;

    /**
     * @var ApplicationProxy|MockObject $mockApplicationProxy
     */
    private $mockApplicationProxy;

    public function setup(): void
    {
        parent::setUp();

        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->mockApplicationProxy = $this->createMock(ApplicationProxy::class);
    }

    public function testMissingConfig()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Config missing for baseUri');

        new ServiceStatusService($this->mockLogger, $this->mockApplicationProxy, ['application' => []]);
    }

    public function testAllOk()
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->will($this->returnValue(200));
        $response->method('getContent')->will($this->returnValue('{"ok": true, "additional": {"ok": true, "status": "test"}}'));

        $this->mockApplicationProxy
            ->method('send')
            ->will($this->returnValue($response));

        $service = new ServiceStatusService($this->mockLogger, $this->mockApplicationProxy, ['application' => ['baseUri' => 'test-url']]);

        $result = $service->check();

        $this->assertEquals([
            'ok' => true,
            'api' => ['ok' => true, 'status-code' => 200],
            'additional' => ['ok' => true, 'status' => 'test']
        ], $result);
    }

    public function testCallReturnsNotOkServices()
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->will($this->returnValue(200));
        $response->method('getContent')->will($this->returnValue('{"ok": false, "additional": {"ok": false, "status": "test"}}'));

        $this->mockApplicationProxy
            ->method('send')
            ->will($this->returnValue($response));

        $service = new ServiceStatusService($this->mockLogger, $this->mockApplicationProxy, ['application' => ['baseUri' => 'test-url']]);

        $result = $service->check();

        $this->assertEquals([
            'ok' => false,
            'api' => ['ok' => true, 'status-code' => 200],
            'additional' => ['ok' => false, 'status' => 'test']
        ], $result);
    }

    public function testApiBadStatus()
    {
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->will($this->returnValue(500));

        $this->mockApplicationProxy
            ->method('send')
            ->will($this->returnValue($response));

        $service = new ServiceStatusService($this->mockLogger, $this->mockApplicationProxy, ['application' => ['baseUri' => 'test-url']]);

        $result = $service->check();

        $this->assertEquals([
            'ok' => false,
            'api' => [
                'ok' => false,
                'status-code' => 500,
                'error' => 'Unable to retrieve statuses, check logs for more details'
            ],
        ], $result);
    }

    public function testExceptionThrownWithSqsQueue()
    {
        $this->mockApplicationProxy
            ->method('send')
            ->will($this->throwException(new Exception()));

        $service = new ServiceStatusService($this->mockLogger, $this->mockApplicationProxy, ['application' => ['baseUri' => 'test-url']]);

        $result = $service->check();

        $this->assertEquals([
            'ok' => false,
            'api' => [
                'ok' => false,
                'error' => 'Threw an exception trying to call, check logs for more details',
            ]
        ], $result);
    }

}
