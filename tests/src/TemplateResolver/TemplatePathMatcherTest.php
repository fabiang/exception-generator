<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\TemplateResolver;

use Fabiang\ExceptionGenerator\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function json_encode;
use function unlink;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher
 */
final class TemplatePathMatcherTest extends TestCase
{
    private TemplatePathMatcher $object;
    private string $configPath;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        vfsStream::setup('root', null, [
            'home'         => [
                '.exception-generator.json' => 'empty yet',
            ],
            'currentdir'   => ['test' => []],
            'expectedpath' => [
                'exception.phtml' => '',
            ],
        ]);

        $this->object = new TemplatePathMatcher(
            vfsStream::url('root/currentdir/test/bar'),
            vfsStream::url('root/home')
        );

        $this->configPath = vfsStream::url('root/home/.exception-generator.json');
    }

    /**
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::__construct
     *
     * @covers ::match
     * @covers ::getPaths
     * @covers ::filterMatchingPaths
     * @covers ::getMostRelatedPath
     */
    public function testMatchProjectPaths(): void
    {
        file_put_contents($this->configPath, json_encode([
            'templatepath' => [
                'projects' => [
                    '/test/foo/bar'                            => '/bar/foo',
                    vfsStream::url('root/currentdir')          => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root')                     => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/Bar') => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/bar') => vfsStream::url('root/expectedpath'),
                ],
            ],
        ]));

        $this->assertSame(vfsStream::url('root/expectedpath'), $this->object->match('exception.phtml'));
    }

    /**
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::match
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::filterMatchingPaths
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::__construct
     *
     * @covers ::getPaths
     * @covers ::getMostRelatedPath
     */
    public function testMatchGlobalTemplatePath(): void
    {
        file_put_contents($this->configPath, json_encode([
            'templatepath' => [
                'projects' => [
                    '/test/foo/bar'                            => '/bar/foo',
                    vfsStream::url('root/currentdir')          => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root')                     => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/Bar') => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/bar') => vfsStream::url('root/unexpectedpath'),
                ],
                'global'   => vfsStream::url('root/expectedpath'),
            ],
        ]));

        $this->assertSame(vfsStream::url('root/expectedpath'), $this->object->match('exception.phtml'));
    }

    /**
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::match
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::filterMatchingPaths
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::getMostRelatedPath
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::__construct
     *
     * @covers ::getPaths
     */
    public function testMatchGlobalPathAndProjectsPathDoesntMatch(): void
    {
        file_put_contents($this->configPath, json_encode([
            'templatepath' => [
                'projects' => [
                    '/test/foo/bar'                            => '/bar/foo',
                    vfsStream::url('root/currentdir')          => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root')                     => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/Bar') => vfsStream::url('root/unexpectedpath'),
                    vfsStream::url('root/currentdir/test/bar') => vfsStream::url('root/unexpectedpath'),
                ],
                'global'   => vfsStream::url('root/unexpectedpath'),
            ],
        ]));

        $this->assertFalse($this->object->match('exception.phtml'));
    }

    /**
     * @uses Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher::__construct
     *
     * @covers ::match
     * @covers ::getPaths
     */
    public function testMatchTemplatePathIsNotConfigured(): void
    {
        file_put_contents($this->configPath, json_encode([]));
        $this->assertFalse($this->object->match('exception.phtml'));

        file_put_contents($this->configPath, json_encode(['templatepath' => null]));
        $this->assertFalse($this->object->match('exception.phtml'));
    }

    /**
     * @covers ::__construct
     * @covers ::match
     */
    public function testMatchConfigFileIsntReadable(): void
    {
        unlink($this->configPath);

        $this->assertFalse($this->object->match('not interesting'));
    }

    /**
     * @covers ::__construct
     * @covers ::match
     */
    public function testMatchConfigurationFileIsBroken(): void
    {
        $this->expectException(RuntimeException::class);
        $this->object->match('foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::match
     */
    public function testMatchConfigurationDoesntReturnArray(): void
    {
        $this->expectException(RuntimeException::class);
        file_put_contents($this->configPath, 'null');
        $this->object->match('foobar');
    }
}
