<?php

declare(strict_types=1);

namespace ApplicationTest\Session\SaveHandler;

use Application\Session\SaveHandler\EncryptedSessionSaveHandler;
use Application\Session\SaveHandler\Factory\EncryptedSessionSaveHandlerFactory;
use Application\Session\SaveHandler\NullSessionSaveHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Filter\Encrypt;
use Laminas\Filter\Decrypt;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

class EncryptedSessionSaveHandlerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function setup(): void
    {
        parent::setUp();

        $this->serviceManager = new ServiceManager();

        $this->serviceManager->setFactory(
            EncryptedSessionSaveHandler::class,
            EncryptedSessionSaveHandlerFactory::class
        );

        $this->serviceManager->setService(
            'SessionEncryptionFilter',
            $this->createMock(Encrypt::class)
        );

        $this->serviceManager->setService(
            'SessionDecryptionFilter',
            $this->createMock(Decrypt::class)
        );
    }

    public function testEncryptedSessionSaveHandlerFactoryCanFetchSaveHandlerFromServiceManager()
    {
        $config = [
            'session' => [
                'config' => [
                    'actual_save_handler' => null
                ]
            ]
        ];

        $this->serviceManager->setService('Config', $config);

        $saveHandler = $this->serviceManager->get(EncryptedSessionSaveHandler::class);

        $this->assertInstanceOf(EncryptedSessionSaveHandler::class, $saveHandler);
    }

    public function testEncryptedSessionSaveHandlerFactoryExceptionThrownWhenSaveHandlerConfigurationDoesNotExist()
    {
        $this->serviceManager->setService('Config', []);

        $this->expectException(ServiceNotCreatedException::class);

        $this->serviceManager->get(EncryptedSessionSaveHandler::class);
    }

    public function testEncryptedSessionSaveHandlerFactoryUsesSaveHandlerServiceWhenSpecified()
    {
        $config = [
            'session' => [
                'config' => [
                    'actual_save_handler' => 'Application\Session\SaveHandler\Test'
                ]
            ]
        ];

        $this->serviceManager->setService('Config', $config);

        $this->serviceManager->setService(
            'Application\Session\SaveHandler\Test',
            $this->createMock(SaveHandlerInterface::class)
        );

        $saveHandler = $this->serviceManager->get(EncryptedSessionSaveHandler::class);

        // Use reflection to check type of protected property.
        $reflect = new ReflectionClass(EncryptedSessionSaveHandler::class);
        $saveHandlerProperty = $reflect->getProperty('sessionSaveHandler');
        $saveHandlerProperty->setAccessible(true);
        $saveHandlerType = get_class($saveHandlerProperty->getValue($saveHandler));

        self::assertStringContainsString('Mock_SaveHandlerInterface', $saveHandlerType);
    }

    public function testEncryptedSessionSaveHandlerFactoryUsesDefaultPHPSaveHandlerWhenNullSpecified()
    {
        $config = [
            'session' => [
                'config' => [
                    'actual_save_handler' => null
                ]
            ]
        ];

        $this->serviceManager->setService('Config', $config);

        $saveHandler = $this->serviceManager->get(EncryptedSessionSaveHandler::class);

        // Use reflection to check type of protect property.
        $reflect = new ReflectionClass(EncryptedSessionSaveHandler::class);
        $saveHandlerProperty = $reflect->getProperty('sessionSaveHandler');
        $saveHandlerProperty->setAccessible(true);
        $saveHandlerType = get_class($saveHandlerProperty->getValue($saveHandler));

        $this->assertEquals(NullSessionSaveHandler::class, $saveHandlerType);
    }
}
