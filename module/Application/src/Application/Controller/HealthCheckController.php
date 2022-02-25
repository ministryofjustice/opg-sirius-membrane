<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Proxy\ApplicationProxy;
use Application\Service\MigrationVersionService;
use Application\Service\ServiceStatusService;
use Doctrine\DBAL\Connection;
use Exception;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\JsonModel;

/**
 * @method Response getResponse()
 */
class HealthCheckController extends AbstractActionController
{
    private Connection $connection;

    /**
     * @var array<mixed> $config
     */
    private $config;

    private ApplicationProxy $applicationProxy;

    private ServiceStatusService $serviceStatusService;

    public function __construct(
        Connection $connection,
        ApplicationProxy $applicationProxy,
        ServiceStatusService $serviceStatusService,
        array $config = null
    ) {
        $this->connection = $connection;
        $this->applicationProxy = $applicationProxy;
        $this->serviceStatusService = $serviceStatusService;
        $this->config = $config;
    }

    /**
     * Used by the ELB to check if the application is alive or needs to be recycled
     *
     * @return ResponseInterface 200 status if ok, anything else is an error
     */
    public function indexAction()
    {
        return $this->getResponse();
    }

    public function migrationVersionAction()
    {
        try {
            $response = $this->applicationProxy->send(
                (new Request())->setUri($this->config['application']['baseUri'] . '/api/health-check/db-version')
            );

            $dbMigrationVersion = json_decode($response->getContent(), true);

            return new JsonModel([
                'membrane-db-migration-version' => $this->getCurrentMigrationVersion(),
                'back-end-db-migration-version' => $dbMigrationVersion['back-end-db-migration-version'],
            ]);
        } catch (Exception $e) {
            $response = $this->getResponse();
            $response->setStatusCode(Response::STATUS_CODE_500);
            $response->setContent($e->getMessage());

            return $response;
        }
    }

    private function getCurrentMigrationVersion(): string
    {
        $migrationVersion = new MigrationVersionService(
            $this->connection,
            $this->config['doctrine']['migrations_configuration']['orm_default']
        );

        return $migrationVersion->getCurrentVersion();
    }

    /**
     * @return JsonModel<mixed>
     */
    public function serviceStatusAction(): JsonModel
    {
        return new JsonModel($this->serviceStatusService->check());
    }
}
