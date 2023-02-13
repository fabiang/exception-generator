<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener
 */
final class RootDirectoryListenerTest extends TestCase
{
    use ProphecyTrait;

    private RootDirectoryListener $object;
    private ObjectProphecy $directoryIterator;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new RootDirectoryListener();

        $this->directoryIterator = $this->prophesize(DirectoryIterator::class);

        $this->directoryIterator->getExtension()->willReturn('php');
        $this->directoryIterator->getBasename()->willReturn('test.php');
        $this->directoryIterator->isDir()->willReturn(false);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onBreak
     */
    public function testOnBreakUnix(): void
    {
        $root = $this->directoryIterator;

        $root->getPath()->willReturn('/');
        $root->getPathname()->willReturn('/test.php');

        $event = new FileEvent($root->reveal());
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
        $this->directoryIterator->getPath()->willReturn('c:\\');
        $this->directoryIterator->getPathname()->willReturn('c:\\test.php');

        $root = $this->directoryIterator->reveal();

        $event = new FileEvent($root);
        $this->object->onBreak($event);
        $this->assertTrue($event->isPropagationStopped());
    }
}
