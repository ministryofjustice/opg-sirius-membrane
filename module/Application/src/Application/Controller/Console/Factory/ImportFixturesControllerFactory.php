<?php

declare(strict_types=1);

namespace Application\Controller\Console\Factory;

use Application\Controller\Console\ImportFixturesController;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ImportFixturesControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|mixed[] $options
     *
     * @return ImportFixturesController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): ImportFixturesController {
        $entityManager = $container->get(EntityManager::class);
        $config = $container->get('Config');
        $fixtureDirectories = $config['doctrine']['fixtures'];
        $purger = new ORMPurger();
        $loader = new Loader();
        $executor = new ORMExecutor($entityManager, $purger);

        return new ImportFixturesController(
            $container->get('console'),
            $purger,
            $loader,
            $executor,
            $fixtureDirectories
        );
    }
}
