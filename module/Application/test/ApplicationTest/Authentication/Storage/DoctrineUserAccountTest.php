<?php

declare(strict_types=1);

namespace ApplicationTest\Authentication\Storage;

use Application\Authentication\Storage\DoctrineUserAccount;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject as MockObject;
use Laminas\Authentication\Storage\StorageInterface;

class DoctrineUserAccountTest extends TestCase
{
    /**
     * @var StorageInterface|MockObject $mockStorage
     */
    private $mockStorage;

    /**
     * @var ObjectRepository|MockObject $mockUserRepository
     */
    private $mockUserRepository;

    public function setup(): void
    {
        $this->mockStorage = $this->createMock(StorageInterface::class);
        $this->mockUserRepository = $this->createMock(ObjectRepository::class);
    }

    public function testIsEmpty()
    {
        $this->mockStorage->method('isEmpty')->willReturn(true);

        $doctrineUserAccount = new DoctrineUserAccount($this->mockStorage, $this->mockUserRepository);

        $result = $doctrineUserAccount->isEmpty();

        $this->assertTrue($result);
    }

    public function testReadNullEmail()
    {
        $this->mockStorage->method('read')->willReturn(null);

        $doctrineUserAccount = new DoctrineUserAccount($this->mockStorage, $this->mockUserRepository);

        $result = $doctrineUserAccount->read();

        $this->assertNull($result);
    }

    public function testReadFromRepository()
    {
        $this->mockStorage->method('read')->willReturn('test@email.com');

        $this->mockUserRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['email' => 'test@email.com']))
            ->willReturn('test result');

        $doctrineUserAccount = new DoctrineUserAccount($this->mockStorage, $this->mockUserRepository);

        $result = $doctrineUserAccount->read();

        $this->assertEquals('test result', $result);
    }

    public function testReadFromCache()
    {
        $this->mockStorage->method('read')->willReturn('test@email.com');

        $this->mockUserRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['email' => 'test@email.com']))
            ->willReturn('test result');

        $doctrineUserAccount = new DoctrineUserAccount($this->mockStorage, $this->mockUserRepository);

        $doctrineUserAccount->read();
        $result = $doctrineUserAccount->read();

        $this->assertEquals('test result', $result);
    }
}
