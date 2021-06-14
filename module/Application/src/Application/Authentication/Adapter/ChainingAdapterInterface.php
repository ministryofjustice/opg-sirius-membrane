<?php

namespace Application\Authentication\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;

interface ChainingAdapterInterface extends AdapterInterface
{
    public function setCredential($credential);

    public function setIdentity($identity);
}
