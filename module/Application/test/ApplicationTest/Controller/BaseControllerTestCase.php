<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Laminas\Log\Logger;
use Laminas\Session\SessionManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Log\Writer\Mock as MockLogWriter;

class BaseControllerTestCase extends AbstractHttpControllerTestCase
{
    public function setup(): void
    {
        $this->setApplicationConfig(include getcwd() . '/config/application.config.php');

        parent::setUp();

        $this->useMockLogger();
    }

    /**
     * Our normal logger writes to stderr, which PHPUnit interprets as test failure
     * when running tests in isolation. The mock logger writes to an array
     * and lets us output the logs after test execution without affecting tests' results.
     *
     * @see tearDown()
     */
    public function useMockLogger()
    {
        $logger = new Logger();
        $logger->addWriter(new MockLogWriter());

        $serviceManager = $this->getApplicationServiceLocator();

        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(Logger::class, $logger);
    }

    protected function teardown(): void
    {
        $logger = $this->getApplicationServiceLocator()->get(Logger::class);

        parent::tearDown();

        if (!$logger instanceof Logger || !$logger->getWriters()) {
            return;
        }

        $writer = $logger->getWriters()->toArray()[0];

        if ($writer instanceof MockLogWriter) {
            foreach ($writer->events as $log) {
                echo json_encode($log) . "\n";
            }
        }
    }

    public function reset($keepPersistence = false)
    {
        $serviceManager = $this->getApplicationServiceLocator();

        /** @var SessionManager $sessionManager */
        $sessionManager = $serviceManager->get(SessionManager::class);

        // destroy the current session so a new one can be started
        $sessionManager->destroy();

        // grab the current logger so we can re-use it after the reset
        $oldLogger = $serviceManager->get(Logger::class);

        parent::reset($keepPersistence);

        $newServiceManager = $this->getApplicationServiceLocator();

        $newServiceManager->setAllowOverride(true);
        $newServiceManager->setService(Logger::class, $oldLogger);

        return $this;
    }
}
