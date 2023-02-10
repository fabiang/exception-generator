<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Event;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Event\CreateExceptionEvent
 */
final class CreateExceptionEventTest extends TestCase
{
    private CreateExceptionEvent $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        vfsStream::setup('test', null, ['bar' => ['foo.php' => 'test content']]);
        $this->object = new CreateExceptionEvent(vfsStream::url('test/bar/foo.php'));
    }

    /**
     * @covers ::getFileName
     * @covers ::__construct
     */
    public function testGetFileName(): void
    {
        $this->assertSame(vfsStream::url('test/bar/foo.php'), $this->object->getFileName());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent::__construct
     *
     * @covers ::getConfirm
     * @covers ::setConfirm
     */
    public function testSetAndGetConfirm(): void
    {
        $this->object->setConfirm('y');
        $this->assertSame('y', $this->object->getConfirm());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent::__construct
     *
     * @covers ::fileExists
     */
    public function testFileExists(): void
    {
        $this->assertTrue($this->object->fileExists(), 'File doesn\'t exist');
    }
}
