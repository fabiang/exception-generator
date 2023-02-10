<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Event;

use DirectoryIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Event\FileEvent
 */
final class FileEventTest extends TestCase
{
    private FileEvent $object;
    private DirectoryIterator $directoryIterator;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        vfsStream::setup('test', null, [
            'directory' => [],
            'file.txt'  => 'test content',
        ]);
        $this->directoryIterator = new DirectoryIterator(vfsStream::url('test'));
        $this->object            = new FileEvent($this->directoryIterator->current());
    }

    /**
     * @covers ::__construct
     * @covers ::getFile
     * @covers ::getExtension
     * @covers ::getBasename
     * @covers ::isDir
     * @covers ::getFileExtension
     * @covers ::getDirname
     */
    public function testGetterHasCorrectValuesDirectory(): void
    {
        $this->directoryIterator->seek(2);
        $object = new FileEvent($this->directoryIterator->current());
        $this->assertSame(vfsStream::url('test/directory'), $object->getFile());
        $this->assertSame('', $object->getExtension());
        $this->assertSame('directory', $object->getBasename());
        $this->assertSame(vfsStream::url('test'), $object->getDirname());
        $this->assertTrue($object->isDir());
    }

    /**
     * @covers ::__construct
     * @covers ::getFile
     * @covers ::getExtension
     * @covers ::getBasename
     * @covers ::isDir
     * @covers ::getFileExtension
     * @covers ::getDirname
     */
    public function testGetterHasCorrectValuesFile(): void
    {
        $this->directoryIterator->seek(3);
        $object = new FileEvent($this->directoryIterator->current());
        $this->assertSame(vfsStream::url('test/file.txt'), $object->getFile());
        $this->assertSame('txt', $object->getExtension());
        $this->assertSame('file.txt', $object->getBasename());
        $this->assertSame(vfsStream::url('test'), $object->getDirname());
        $this->assertFalse($object->isDir());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent::__construct
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent::getFileExtension
     *
     * @covers ::getNamespace
     * @covers ::setNamespace
     */
    public function testSetAndGetNamespace(): void
    {
        $this->object->setNamespace('test');
        $this->assertSame('test', $this->object->getNamespace());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent::__construct
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent::getFileExtension
     *
     * @covers ::getLoopedDirectories
     * @covers ::setLoopedDirectories
     */
    public function testSetAndGetLoopedDirectories(): void
    {
        $this->object->setLoopedDirectories(['1', '2', '3']);
        $this->assertSame(['1', '2', '3'], $this->object->getLoopedDirectories());
    }
}
