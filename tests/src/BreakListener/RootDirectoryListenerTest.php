<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\TestHelper\MockDirectoryIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener
 */
final class RootDirectoryListenerTest extends TestCase
{
    private RootDirectoryListener $object;
    private MockObject $mockedDirectoryIterator;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new RootDirectoryListener();

        $this->mockedDirectoryIterator = $this->createMock(
            MockDirectoryIterator::class
        );

        $this->mockedDirectoryIterator->expects($this->once())
            ->method('getExtension')
            ->willReturn('php');

        $this->mockedDirectoryIterator->expects($this->once())
            ->method('getBasename')
            ->willReturn('test.php');

        $this->mockedDirectoryIterator->expects($this->once())
            ->method('isDir')
            ->willReturn(false);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onBreak
     */
    public function testOnBreakUnix(): void
    {
        $root = $this->mockedDirectoryIterator;

        $root->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $root->expects($this->once())
            ->method('getPathname')
            ->willReturn('/test.php');

        $event = new FileEvent($root);
        $this->object->onBreak($event);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onBreak
     */
    public function testOnBreakWindows(): void
    {
        $root = $this->mockedDirectoryIterator;

        $root->expects($this->once())
            ->method('getPath')
            ->willReturn('c:\\');

        $root->expects($this->once())
            ->method('getPathname')
            ->willReturn('c:\\test.php');

        $event = new FileEvent($root);
        $this->object->onBreak($event);
        $this->assertTrue($event->isPropagationStopped());
    }
}
