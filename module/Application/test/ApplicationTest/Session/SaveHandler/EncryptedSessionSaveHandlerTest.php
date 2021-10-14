<?php

declare(strict_types=1);

namespace ApplicationTest\Session\SaveHandler;

use Application\Session\SaveHandler\EncryptedSessionSaveHandler;
use PHPUnit\Framework\TestCase;
use Laminas\Filter\Encrypt;
use Laminas\Filter\Decrypt;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

class EncryptedSessionSaveHandlerTest extends TestCase
{
    protected $mockSessionSaveHandler;
    protected $mockEncryptionFilter;
    protected $mockDecryptionFilter;

    /**
     * @var EncryptedSessionSaveHandler
     */
    protected $encryptedSessionSaveHandler;

    public function setup(): void
    {
        parent::setUp();

        $this->mockSessionSaveHandler = $this->createMock(SaveHandlerInterface::class);
        $this->mockEncryptionFilter = $this->createMock(Encrypt::class);
        $this->mockDecryptionFilter = $this->createMock(Decrypt::class);

        $this->encryptedSessionSaveHandler = new EncryptedSessionSaveHandler(
            $this->mockSessionSaveHandler,
            $this->mockEncryptionFilter,
            $this->mockDecryptionFilter
        );
    }

    public function testEncryptedSessionSaveHandlerOpenIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('open')
            ->with($this->equalTo('testPath'), $this->equalTo('testName'))
            ->will($this->returnValue(true));

        $result = $this->encryptedSessionSaveHandler->open('testPath', 'testName');

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerCloseIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $result = $this->encryptedSessionSaveHandler->close();

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerReadIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('read')
            ->with($this->equalTo('testId'))
            ->will($this->returnValue('testData'));

        $this->mockDecryptionFilter->expects($this->once())
            ->method('filter')
            ->with($this->equalTo('testData'))
            ->will($this->returnValue('decryptedData'));

        $result = $this->encryptedSessionSaveHandler->read('testId');

        $this->assertEquals('decryptedData', $result);
    }

    public function testEncryptedSessionSaveHandlerEmptyReadFromBackingMethodReturnsEmpty()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('read')
            ->with($this->equalTo('testId'))
            ->will($this->returnValue(''));

        $this->mockDecryptionFilter->expects($this->never())
            ->method('filter');

        $result = $this->encryptedSessionSaveHandler->read('testId');

        $this->assertEmpty($result);
    }

    public function testEncryptedSessionSaveHandlerFalseReadFromBackingMethodReturnsFalse()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('read')
            ->with($this->equalTo('testId'))
            ->will($this->returnValue(false));

        $this->mockDecryptionFilter->expects($this->never())
            ->method('filter');

        $result = $this->encryptedSessionSaveHandler->read('testId');

        $this->assertFalse($result);
    }

    public function testEncryptedSessionSaveHandlerWriteIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('write')
            ->with($this->equalTo('testId'), $this->equalTo('encryptedData'))
            ->will($this->returnValue(true));

        $this->mockEncryptionFilter->expects($this->once())
            ->method('filter')
            ->with($this->equalTo('testData'))
            ->will($this->returnValue('encryptedData'));

        $result = $this->encryptedSessionSaveHandler->write('testId', 'testData');

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerEmptyWriteToBackingMethodReturnsEmpty()
    {
        $this->mockSessionSaveHandler->expects($this->once())->method('write')->willReturn(true);

        $this->mockEncryptionFilter->expects($this->never())
            ->method('filter');

        $result = $this->encryptedSessionSaveHandler->write('testId', '');

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerEmptyWriteFromBackingMethodReturnsFalse()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('write')->willReturn(true);

        $this->mockEncryptionFilter->expects($this->never())
            ->method('filter');

        $result = $this->encryptedSessionSaveHandler->write('testId', '');

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerDestroyIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('destroy')
            ->with($this->equalTo('testId'))
            ->will($this->returnValue(true));

        $result = $this->encryptedSessionSaveHandler->destroy('testId');

        $this->assertTrue($result);
    }

    public function testEncryptedSessionSaveHandlerGarbageCollectionIsCalled()
    {
        $this->mockSessionSaveHandler->expects($this->once())
            ->method('gc')
            ->with($this->equalTo(123456789))
            ->will($this->returnValue(true));

        $result = $this->encryptedSessionSaveHandler->gc(123456789);

        $this->assertTrue($result);
    }
}
