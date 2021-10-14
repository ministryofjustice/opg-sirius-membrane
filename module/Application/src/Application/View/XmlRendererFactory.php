<?php

declare(strict_types=1);

namespace Application\View;

use Application\View\Renderer\XmlRenderer;
use Application\View\Strategy\XmlStrategy;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class XmlRendererFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     * @return XmlStrategy
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): XmlStrategy
    {
        $xmlRenderer = new XmlRenderer();

        return new XmlStrategy($xmlRenderer);
    }
}
