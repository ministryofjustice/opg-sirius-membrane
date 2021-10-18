<?php

declare(strict_types=1);

namespace Application\Session\SaveHandler\Factory;

use Application\Session\SaveHandler\EncryptedSessionSaveHandler;
use Application\Session\SaveHandler\NullSessionSaveHandler;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Exception\RuntimeException;

/**
 * Factory used to instantiate an encrypted session save handler.
 */
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
        $config = $container->get('Config');

        if (!isset($config['session']['config']) || !array_key_exists('actual_save_handler', $config['session']['config'])) {
            throw new RuntimeException('Missing "actual_save_handler" config option in the session config array.');
        }

        /** @var string|null $saveHandlerClass */
        $saveHandlerClass = $config['session']['config']['actual_save_handler'];

        // To support encryption of Laminas Framework's default Session Save Handler
        // (default PHP implementation), logic is added to wrap the default
        // SessionHandler object that implements Laminas's SaveHandlerInterface,
        // which it does natively.

        if (is_null($saveHandlerClass)) {
            $sessionSaveHandler = new NullSessionSaveHandler();
        } else {
            $sessionSaveHandler = $container->get($saveHandlerClass);
        }

        $encryptFilter = $container->get('SessionEncryptionFilter');
        $decryptFilter = $container->get('SessionDecryptionFilter');

        return new EncryptedSessionSaveHandler($sessionSaveHandler, $encryptFilter, $decryptFilter);
    }
}
