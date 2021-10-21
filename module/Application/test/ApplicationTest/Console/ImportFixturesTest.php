<?php

namespace ApplicationTest\Service;

use Application\Console\ImportFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFixturesTest extends TestCase
{
    protected ORMPurger|MockObject $mockPurger;
    protected Loader|MockObject $mockLoader;
    protected ORMExecutor|MockObject $mockExecutor;
    protected InputInterface|MockObject $mockInput;
    protected OutputInterface|MockObject $mockOutput;

    public function setup(): void
    {
        parent::setUp();

        /** @var ORMPurger|MockObject */
        $this->mockPurger = $this->createMock(ORMPurger::class);

        /** @var Loader|MockObject */
        $this->mockLoader = $this->createMock(Loader::class);

        /** @var ORMExecutor|MockObject */
        $this->mockExecutor = $this->createMock(ORMExecutor::class);

        /** @var InputInterface|MockObject */
        $this->mockInput = $this->createMock(InputInterface::class);

        /** @var OutputInterface|MockObject */
        $this->mockOutput = $this->createMock(OutputInterface::class);
    }

    public function testImportFixtures()
    {
        $sut = new ImportFixtures($this->mockPurger, $this->mockLoader, $this->mockExecutor, []);

        $this->mockInput->expects($this->exactly(2))
            ->method('getOption')
            ->withConsecutive(['append'], ['purge-with-truncate'])
            ->willReturnOnConsecutiveCalls(false, false);

        $this->mockLoader->expects($this->once())
            ->method('getFixtures')
            ->willReturn([]);

        $this->mockOutput->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(['<fg=yellow>No fixtures found</>'], ['<fg=green>DONE</>']);

        $this->assertEquals(0, $sut->run($this->mockInput, $this->mockOutput));
    }

    public function testImportFixturesWithTruncate()
    {
        $sut = new ImportFixtures($this->mockPurger, $this->mockLoader, $this->mockExecutor, []);

        $this->mockInput->expects($this->exactly(2))
            ->method('getOption')
            ->withConsecutive(['append'], ['purge-with-truncate'])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->mockPurger->expects($this->once())
            ->method('setPurgeMode')
            ->with(ORMPurger::PURGE_MODE_TRUNCATE);

        $this->mockLoader->expects($this->once())
            ->method('getFixtures')
            ->willReturn([]);

        $this->mockOutput->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(['<fg=yellow>No fixtures found</>'], ['<fg=green>DONE</>']);

        $this->assertEquals(0, $sut->run($this->mockInput, $this->mockOutput));
    }

    public function testImportFixturesWithFixtureDirectories()
    {
        $sut = new ImportFixtures($this->mockPurger, $this->mockLoader, $this->mockExecutor, ['/root', '/migrations']);

        $this->mockInput->expects($this->exactly(2))
            ->method('getOption')
            ->withConsecutive(['append'], ['purge-with-truncate'])
            ->willReturnOnConsecutiveCalls(false, false);

        $this->mockLoader->expects($this->exactly(2))
            ->method('loadFromDirectory')
            ->withConsecutive(['/root'], ['/migrations']);

        $this->mockLoader->expects($this->once())
            ->method('getFixtures')
            ->willReturn([]);

        $this->mockOutput->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(['<fg=yellow>No fixtures found</>'], ['<fg=green>DONE</>']);

        $this->assertEquals(0, $sut->run($this->mockInput, $this->mockOutput));
    }

    public function testImportFixturesWithOtherFixtures()
    {
        $sut = new ImportFixtures($this->mockPurger, $this->mockLoader, $this->mockExecutor, []);

        $this->mockInput->expects($this->exactly(2))
            ->method('getOption')
            ->withConsecutive(['append'], ['purge-with-truncate'])
            ->willReturnOnConsecutiveCalls(false, false);

        $this->mockLoader->expects($this->once())
            ->method('getFixtures')
            ->willReturn(['fixture1', 'fixture2']);

        $this->mockExecutor->expects($this->once())
            ->method('execute')
            ->with(['fixture1', 'fixture2'], false);

        $this->mockOutput->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(['Loading 2 fixtures'], ['<fg=green>DONE</>']);

        $this->assertEquals(0, $sut->run($this->mockInput, $this->mockOutput));
    }

    public function testImportFixturesWithOtherFixturesAppended()
    {
        $sut = new ImportFixtures($this->mockPurger, $this->mockLoader, $this->mockExecutor, []);

        $this->mockInput->expects($this->exactly(2))
            ->method('getOption')
            ->withConsecutive(['append'], ['purge-with-truncate'])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->mockLoader->expects($this->once())
            ->method('getFixtures')
            ->willReturn(['fixture1', 'fixture2']);

        $this->mockExecutor->expects($this->once())
            ->method('execute')
            ->with(['fixture1', 'fixture2'], true);

        $this->mockOutput->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(['Loading 2 fixtures'], ['<fg=green>DONE</>']);

        $this->assertEquals(0, $sut->run($this->mockInput, $this->mockOutput));
    }
}
