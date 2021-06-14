<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Proxy\ApplicationProxy;
use Exception;
use Throwable;
use Laminas\Http\Request;
use Laminas\Log\LoggerInterface;

class ServiceStatusService
{
    public const SERVICE_STATUS_URI = '/api/health-check/service-status';
    public const KEY_OK = 'ok';
    public const KEY_STATUS_CODE = 'status-code';
    public const KEY_ERROR = 'error';

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @var ApplicationProxy $applicationProxy
     */
    private $applicationProxy;

    /**
     * @var string $baseUri
     */
    private $baseUri;

    /**
     * ServiceStatusService constructor.
     *
     * @param LoggerInterface $logger
     * @param ApplicationProxy $applicationProxy
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct(LoggerInterface $logger, ApplicationProxy $applicationProxy, array $config)
    {
        $this->logger = $logger;
        $this->applicationProxy = $applicationProxy;

        if (!$config['application'] || !$config['application']['baseUri']) {
            throw new Exception('Config missing for baseUri');
        }

        $this->baseUri = $config['application']['baseUri'];
    }

    public function check(): array
    {
        // Default status in case the API call fails
        $apiServices = [ServiceStatusService::KEY_OK => false];

        // Default to failure in case of errors calling the api
        $api = [ServiceStatusService::KEY_OK => false];

        try {
            $response = $this->applicationProxy->send(
                (new Request())->setUri($this->baseUri . ServiceStatusService::SERVICE_STATUS_URI)
            );

            $statusCode = $response->getStatusCode();
            $api[ServiceStatusService::KEY_STATUS_CODE] = $statusCode;

            if ($statusCode === 200) {
                $api[ServiceStatusService::KEY_OK] = true;

                // Add the status details contained in the response to the service status array
                $apiServices = array_merge($apiServices, json_decode($response->getContent(), true));
            } else {
                $api[ServiceStatusService::KEY_ERROR] = 'Unable to retrieve statuses, check logs for more details';
            }
        } catch (Throwable $t) {
            $this->logger->err($t->getMessage());
            $api[ServiceStatusService::KEY_ERROR] = 'Threw an exception trying to call, check logs for more details';
        }

        return array_merge([
            'api' => $api,
            ServiceStatusService::KEY_OK => ($api[ServiceStatusService::KEY_OK] && $apiServices[ServiceStatusService::KEY_OK]),
        ], $apiServices);
    }
}
