<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\Resolver\NamespaceResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener
 */
final class PHPFileListenerTest extends TestCase
{
    use ProphecyTrait;

    private PHPFileListener $object;
    private ObjectProphecy $namespaceResolver;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->namespaceResolver = $this->prophesize(NamespaceResolver::class);
        $this->object            = new PHPFileListener($this->namespaceResolver->reveal());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     *
     * @covers ::onFile
     * @covers ::__construct
     */
    public function testOnFile(): void
    {
        vfsStream::setup('test', null, ['Test.php' => 'composer json content']);

        $this->namespaceResolver->resolve(
            vfsStream::url('test/Test.php'),
            []
        )->shouldBeCalledOnce()->willReturn('MyNamespace\\');

        $directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $directoryIterator->seek(2);
        $event = new FileEvent($directoryIterator);

        $this->object->onFile($event);
        $this->assertSame('MyNamespace\\', $event->getNamespace());
    }
}
