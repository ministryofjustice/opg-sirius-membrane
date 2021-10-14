<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\StorageInterface;

class AuthenticationServiceConstructor
{
    /** @var StorageInterface */
    private $bypassMembraneStorage;
    /** @var StorageInterface */
    private $normalStorage;
    /** @var AdapterInterface */
    private $adapter;

    public function __construct(
        StorageInterface $bypassMembraneStorage,
        StorageInterface $normalStorage,
        AdapterInterface $adapter
    ) {
        $this->bypassMembraneStorage = $bypassMembraneStorage;
        $this->normalStorage = $normalStorage;
        $this->adapter = $adapter;
    }

    public function getNormal(): AuthenticationService
    {
        return new AuthenticationService($this->normalStorage, $this->adapter);
    }

    public function getBypassMembrane(): AuthenticationService
    {
        return new AuthenticationService($this->bypassMembraneStorage, $this->adapter);
    }
}
