<?php

declare(strict_types=1);

namespace Application\View\Renderer;

use Laminas\View\Model\ModelInterface;
use Laminas\View\Renderer\RendererInterface as Renderer;
use Laminas\View\Renderer\TreeRendererInterface;
use Laminas\View\Resolver\ResolverInterface as Resolver;
use Application\View\Model\XmlModel;
use Exception;

/**
 * XML renderer
 */
class XmlRenderer implements Renderer, TreeRendererInterface
{
    protected $resolver;

    public function getEngine(): self
    {
        return $this;
    }

    public function setResolver(Resolver $resolver): self
    {
        $this->resolver = $resolver;
        return $this;
    }

    public function canRenderTrees(): bool
    {
        return false;
    }

    /**
     * @param ModelInterface<mixed>|string $nameOrModel
     * @param null $values
     * @return string
     * @throws Exception
     */
    public function render($nameOrModel, $values = null): string
    {
        if ($nameOrModel instanceof XmlModel) {
            return $nameOrModel->serialize();
        }

        throw new Exception('Unable to render xml without XmlViewModel');
    }
}
