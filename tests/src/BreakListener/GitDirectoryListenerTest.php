<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\BreakListener\GitDirectoryListener
 */
final class GitDirectoryListenerTest extends TestCase
{
    private GitDirectoryListener $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new GitDirectoryListener();
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onBreak
     */
    public function testOnBreakIsDotGit(): void
    {
        vfsStream::setup('test', null, ['.git' => []]);

        $directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $directoryIterator->seek(2);
        $event = new FileEvent($directoryIterator);

        $this->object->onBreak($event);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onBreak
     */
    public function testOnBreakIsDotGitButNoDirectory(): void
    {
        vfsStream::setup('test', null, ['.git' => 'is a file']);

        $directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $directoryIterator->seek(2);
        $event = new FileEvent($directoryIterator);

        $this->object->onBreak($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * @covers Fabiang\ExceptionGenerator\BreakListener\AbstractBreakListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            ['file.break' => ['onBreak']],
            $this->object->getSubscribedEvents()
        );
    }
}
