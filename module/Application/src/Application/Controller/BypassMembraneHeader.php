<?php

declare(strict_types=1);

namespace Application\Controller;

trait BypassMembraneHeader
{
    public function hasBypassMembraneHeader(): bool
    {
        /** @var \Laminas\Http\Request */
        $request = $this->getRequest();
        /** @var \Laminas\Http\Header\HeaderInterface|false */
        $header = $request->getHeader('OPG-Bypass-Membrane');

        $query = $request->getQuery('OPG-Bypass-Membrane') === '1';

        return ($header && $header->getFieldValue() === '1') || $query;
    }
}
