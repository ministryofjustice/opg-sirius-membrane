<?php

declare(strict_types=1);

namespace Application\Session\SaveHandler\Factory;

use Application\Session\SaveHandler\EncryptedSessionSaveHandler;
use Interop\Container\ContainerInterface;
use Laminas\Filter\Decrypt;
use Laminas\Filter\Encrypt;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

class EncryptedSessionSaveHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return EncryptedSessionSaveHandler
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EncryptedSessionSaveHandler
    {
        $sessionSaveHandler = $container->get(SaveHandlerInterface::class);
        $encryptFilter = $container->get(Encrypt::class);
        $decryptFilter = $container->get(Decrypt::class);

        return new EncryptedSessionSaveHandler($sessionSaveHandler, $encryptFilter, $decryptFilter);
    }
}
