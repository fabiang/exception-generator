<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use Fabiang\ExceptionGenerator\BreakListener\GitDirectoryListener;
use Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener;
use Fabiang\ExceptionGenerator\DirLoopListener\ExceptionDirListener;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function get_class;

/**
 * @coversDefaultClass \Fabiang\ExceptionGenerator\Generator\RecursiveParentExceptionResolver
 */
final class RecursiveParentExceptionResolverTest extends TestCase
{
    use ProphecyTrait;

    private RecursiveParentExceptionResolver $object;
    private ObjectProphecy $eventDispatcher;

    protected function setUp(): void
    {
        vfsStream::setup('test', null, [
            'foo' => [
                'bar' => [
                    'file1.txt' => 'content1',
                    'file2.txt' => 'content2',
                    'file3.txt' => 'content3',
                ],
            ],
        ]);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->object = new RecursiveParentExceptionResolver($this->eventDispatcher->reveal());
    }

    /**
     * @test
     * @covers ::resolveExceptionDirs
     * @covers ::getDirectoryContents
     */
    public function resolveExceptionDirs(): void
    {
        $path = vfsStream::url('test/foo/bar/baz');

        $propagationStopped = 0;

        $this->eventDispatcher->dispatch(
            Argument::type(FileEvent::class),
            'dir.loop'
        )
            ->shouldBeCalledTimes(2)
            ->will(function (array $args) use (&$propagationStopped) {
                /** @var FileEvent $event */
                $event = $args[0];
                if ($propagationStopped++ === 1) {
                    $event->stopPropagation();
                    $event->setParentExceptionDir();
                }
                return $event;
            });

        $propagationStopped2 = 0;

        $this->eventDispatcher->dispatch(
            Argument::type(FileEvent::class),
            'file.break'
        )
            ->shouldBeCalledTimes(2)
            ->will(function (array $args) use (&$propagationStopped2) {
                /** @var FileEvent $event */
                $event = $args[0];
                if ($propagationStopped2++ === 1) {
                    $event->stopPropagation();
                    $event->setParentExceptionDir();
                }
                return $event;
            });

        $this->assertSame(['vfs://test/foo/bar/Exception'], $this->object->resolveExceptionDirs($path));
    }

    /**
     * @test
     * @covers ::resolveExceptionDirs
     * @covers ::getDirectoryContents
     */
    public function resolveExceptionDirsRoot(): void
    {
        $path = vfsStream::url('test/foo/bar/baz');

        $this->eventDispatcher->dispatch(
            Argument::type(FileEvent::class),
            'dir.loop'
        )
            ->shouldBeCalledTimes(5)
            ->will(function (array $args) {
                /** @var FileEvent $event */
                $event = $args[0];
                return $event;
            });

        $this->eventDispatcher->dispatch(
            Argument::type(FileEvent::class),
            'file.break'
        )
            ->shouldBeCalledTimes(5)
            ->will(function (array $args) {
                /** @var FileEvent $event */
                $event = $args[0];
                return $event;
            });

        $this->assertNull($this->object->resolveExceptionDirs($path));
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::registerDefaultListeners
     */
    public function addDefaultSubscribers(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->addSubscriber(Argument::that(fn (EventSubscriberInterface $es) => match (get_class($es)) {
            GitDirectoryListener::class => true,
            RootDirectoryListener::class => true,
            ExceptionDirListener::class => true,
            default => $this->fail('Unknown listener')
        }))
        ->shouldBeCalledTimes(3);

        $object = new RecursiveParentExceptionResolver($eventDispatcher->reveal());
        $this->assertInstanceOf(EventDispatcherInterface::class, $object->getEventDispatcher());
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::getEventDispatcher
     */
    public function getEventDispatcher(): void
    {
        $this->assertInstanceOf(EventDispatcherInterface::class, $this->object->getEventDispatcher());
    }
}
