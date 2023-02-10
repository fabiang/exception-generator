<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\Resolver\ComposerResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener
 */
final class ComposerJsonListenerTest extends TestCase
{
    private ComposerJsonListener $object;
    private MockObject $composerResolver;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->composerResolver = $this->createMock(ComposerResolver::class);
        $this->object           = new ComposerJsonListener($this->composerResolver);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onFile
     * @covers ::__construct
     */
    public function testOnFile(): void
    {
        vfsStream::setup('test', null, ['composer.json' => 'composer json content']);

        $this->composerResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $this->equalTo(vfsStream::url('test/composer.json')),
                $this->equalTo([])
            )
            ->will($this->returnValue('MyNamespace\\'));

        $directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $directoryIterator->seek(2);
        $event = new FileEvent($directoryIterator);

        $this->object->onFile($event);
        $this->assertSame('MyNamespace\\', $event->getNamespace());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener::__construct
     *
     * @covers Fabiang\ExceptionGenerator\FileLoopListener\AbstractFileLoopListener
     */
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(['file.loop' => ['onFile', 0]], $this->object->getSubscribedEvents());
    }
}
