<?php

declare(strict_types=1);

namespace Application;

use Application\ContentNegotiation\XmlContentTypeListener;
use Application\View\Strategy\XmlStrategy;
use Laminas\EventManager\EventInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Throwable;
use Exception;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;
use Laminas\View\View;
use Laminas\Log\Logger;

class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $serviceManager = $e->getApplication()->getServiceManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $request = $e->getRequest();
        $response = $e->getResponse();

        $logger = $serviceManager->get(Logger::class);
        //catches exceptions for errors during dispatch
        $loggerConfig = $serviceManager->get('Config')['sirius_logger'];
        $dispatchErrorPriority = empty($loggerConfig['dispatchErrorPriority']) ? 1 : $loggerConfig['dispatchErrorPriority'];

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function (MvcEvent $event) use ($logger) {
            /** @var Exception $exception */
            $exception = $event->getParam('exception');

            if ($exception instanceof Throwable) {
                $logger->crit(
                    'Exception: [' . $exception->getMessage() . ']',
                    [
                        'category' => 'Dispatch',
                        'stackTrace' => $exception->getTraceAsString(),
                    ]
                );
            }
        }, $dispatchErrorPriority);

        if ($request instanceof Request && $request->getRequestUri() !== '/api/ddc') {
            $eventManager->attach(MvcEvent::EVENT_RENDER, function (EventInterface $e) use ($serviceManager) {
                /** @var View $view */
                $view = $serviceManager->get(View::class);

                /** @var XmlStrategy $xmlStrategy */
                $xmlStrategy = $serviceManager->get(XmlStrategy::class);

                $xmlStrategy->attach($view->getEventManager(), 500);
            });
            $eventManager->attach(MvcEvent::EVENT_ROUTE, new XmlContentTypeListener(), -630);
        }

        /** @var SessionManager $session */
        $session = $e->getApplication()->getServiceManager()->get(SessionManager::class);

        if ($request instanceof Request) {
            // start the session and create the Laminas_Auth namespace which is needed later on for authentication
            new SessionContainer('Laminas_Auth', $session);
        }
    }

    /**
     * @return array<mixed>
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
