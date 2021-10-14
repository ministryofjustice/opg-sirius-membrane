<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

class SessionManagerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return SessionManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SessionManager
    {
        $session = $container->get('config')['session']['config'];

        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($session['options']);

        $sessionStorage = null;
        if (isset($session['storage'])) {
            $class = $session['storage'];
            $sessionStorage = new $class();
        }

        $sessionSaveHandler = null;
        if (isset($session['save_handler'])) {
            // class should be fetched from service manager since it will require constructor arguments
            $sessionSaveHandler = $container->get($session['save_handler']);
            $sessionConfig->setSaveHandler($sessionSaveHandler);
        }

        $sessionManager = new SessionManager($sessionConfig, $sessionStorage, $sessionSaveHandler);
        Container::setDefaultManager($sessionManager);

        // Set Session ID if it is provided inside the special request header.
        $request = $container->get('Request');

        if ($request instanceof HttpRequest) {
            $header = $request->getHeader('http-secure-token');

            if ($header instanceof HeaderInterface) {
                $sessionManager->setId($header->getFieldValue());
            }
        }

        return $sessionManager;
    }
}
