<?php

declare(strict_types=1);

namespace Profiling;

use Laminas\Http\Response as HttpResponse;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Log\Logger;

class Module implements ConfigProviderInterface, ServiceProviderInterface
{
    /**
     * @param MvcEvent $event
     */
    public function onBootstrap(MvcEvent $event)
    {
        $serviceManager = $event->getApplication()->getServiceManager();
        $serviceManager->get('Doctrine\ORM\EntityManager')
            ->getConfiguration()
            ->setSQLLogger(
                $serviceManager->get('Doctrine\DBAL\Logging\DebugStack')
            );

        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);
    }

    /**
     * @param MvcEvent $event
     */
    public function onFinish(MvcEvent $event)
    {
        $serviceManager = $event->getApplication()->getServiceManager();
        $debugStack = $serviceManager->get('Doctrine\DBAL\Logging\DebugStack');

        $response = $event->getResponse();
        if ($response instanceof HttpResponse) {
            /** @var HttpResponse $response */
            $response->getHeaders()->addHeaderLine('x-doctrine-query-count', $debugStack->currentQuery);
        }

        $serviceManager->get(Logger::class)->info(
            'Response Profile',
            [
                'query_count' => $debugStack->currentQuery,
                'queries' => $debugStack->queries,
            ]
        );
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'Doctrine\DBAL\Logging\DebugStack' => 'Doctrine\DBAL\Logging\DebugStack',
            ],
        ];
    }
}
