<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\Resolver\NamespaceResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener
 */
final class PHPFileListenerTest extends TestCase
{
    private PHPFileListener $object;
    private MockObject $namespaceResolver;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->namespaceResolver = $this->createMock(NamespaceResolver::class);
        $this->object            = new PHPFileListener($this->namespaceResolver);
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

        $this->namespaceResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $this->equalTo(vfsStream::url('test/Test.php')),
                $this->equalTo([])
            )
            ->will($this->returnValue('MyNamespace\\'));

        $directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $directoryIterator->seek(2);
        $event = new FileEvent($directoryIterator);

        $this->object->onFile($event);
        $this->assertSame('MyNamespace\\', $event->getNamespace());
    }
}
