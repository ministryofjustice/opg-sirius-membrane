<?php

declare(strict_types=1);

namespace Application\Console\Factory;

use Application\Console\ImportFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ImportFixturesFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|mixed[] $options
     *
     * @return ImportFixtures
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ImportFixtures {
        $entityManager = $container->get(EntityManager::class);

        $purger = new ORMPurger();
        $loader = new Loader();
        $executor = new ORMExecutor($entityManager, $purger);

        $fixtureDirectories = $container->get('Config')['doctrine']['fixtures'];

        return new ImportFixtures(
            $purger,
            $loader,
            $executor,
            $fixtureDirectories
        );
    }
}
