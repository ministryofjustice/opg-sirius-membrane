<?php

namespace Application\View\Strategy;

use Application\View\Renderer\XmlRenderer as XmlRenderer;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\View\ViewEvent;

class XmlStrategy extends AbstractListenerAggregate
{
    /**
     * Character set for associated content-type
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * Multibyte character sets that will trigger a binary content-transfer-encoding
     *
     * @var array
     */
    protected $multibyteCharsets = [
        'UTF-16',
        'UTF-32',
    ];

    protected $renderer;

    public function __construct(XmlRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function selectRenderer(ViewEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof \Application\View\Model\XmlModel) {
            return $this->renderer;
        }
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], $priority);
    }

    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            return;
        }

        $result = $e->getResult();
        if (!is_string($result)) {
            return;
        }

        // Populate response
        /** @var Response $response */
        $response = $e->getResponse();
        $response->setContent($result);
        $headers = $response->getHeaders();

        $contentType = 'application/xml';

        $contentType .= '; charset=' . $this->charset;
        $headers->addHeaderLine('content-type', $contentType);

        if (in_array(strtoupper($this->charset), $this->multibyteCharsets)) {
            $headers->addHeaderLine('content-transfer-encoding', 'BINARY');
        }
    }
}
