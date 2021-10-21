<?php

namespace Application\Console;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFixtures extends Command
{
    public function __construct(
        private ORMPurger $purger,
        private Loader $loader,
        private ORMExecutor $executor,
        private array $fixtureDirectories
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('append', null, InputOption::VALUE_OPTIONAL, 'The first ID or starting date to index from')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_OPTIONAL, 'The first ID or starting date to index from');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $append = $input->getOption('append');
        $truncate = $input->getOption('purge-with-truncate');

        if (!empty($truncate)) {
            $this->purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        }

        foreach ($this->fixtureDirectories as $dir) {
            $this->loader->loadFromDirectory($dir);
        }

        $fixtures = $this->loader->getFixtures();

        if ($fixtures) {
            $output->writeln(sprintf('Loading %d fixtures', count($fixtures)));
            $this->executor->execute($fixtures, $append);
        } else {
            $output->writeln('<fg=yellow>No fixtures found</fg>');
        }

        $output->writeln('<fg=green>DONE</fg>');

        return 0;
    }
}
