<?php

declare(strict_types=1);

namespace Application\Controller\Console;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Laminas\Console\Adapter\AdapterInterface as Console;
use Laminas\Console\ColorInterface;
use Laminas\Console\Request;

class ImportFixturesController extends ConsoleController
{
    private Console $console;

    /**
     * @var array<string>
     */
    private array $fixtureDirectories;

    private ORMPurger $purger;

    private Loader $loader;

    private ORMExecutor $executor;

    /**
     * ImportFixturesController constructor.
     * @param Console $console
     * @param ORMPurger $purger
     * @param Loader $loader
     * @param ORMExecutor $executor
     * @param array<string> $fixtureDirectories
     */
    public function __construct(
        Console $console,
        ORMPurger $purger,
        Loader $loader,
        ORMExecutor $executor,
        array $fixtureDirectories
    ) {
        $this->console = $console;
        $this->fixtureDirectories = $fixtureDirectories;
        $this->purger = $purger;
        $this->loader = $loader;
        $this->executor = $executor;
    }

    public function importAction(): void
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $append = $request->getParam('append');
        $truncate = $request->getParam('purge-with-truncate');

        if (!empty($truncate)) {
            $this->purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        }

        foreach ($this->fixtureDirectories as $dir) {
            $this->loader->loadFromDirectory($dir);
        }

        $fixtures = $this->loader->getFixtures();

        if ($fixtures) {
            $this->console->writeLine(sprintf('Loading %d fixtures', count($fixtures)));
            $this->executor->execute($fixtures, $append);
        } else {
            $this->console->writeLine('No fixtures found', null, ColorInterface::YELLOW);
        }

        $this->console->writeLine('DONE', null, ColorInterface::GREEN);
    }
}
