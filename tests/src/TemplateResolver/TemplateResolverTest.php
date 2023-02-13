<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\TemplateResolver;

use Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function file_put_contents;
use function realpath;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\TemplateResolver\TemplateResolver
 */
final class TemplateResolverTest extends TestCase
{
    use ProphecyTrait;

    private TemplateResolver $object;
    private ObjectProphecy $templatePathMatcher;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['home' => []]);

        $this->templatePathMatcher = $this->prophesize(
            TemplatePathMatcher::class
        );

        $this->object = new TemplateResolver(
            vfsStream::url('root/home'),
            $this->templatePathMatcher->reveal()
        );
    }

    /**
     * @covers ::resolve
     * @covers ::getTemplatePath
     * @covers ::__construct
     */
    public function testResolveTemplateExistsInGivenPath(): void
    {
        file_put_contents(vfsStream::url('root/home/exception.phtml'), '');
        $this->assertSame(vfsStream::url('root/home/exception.phtml'), $this->object->resolve('exception.phtml'));
    }

    /**
     * @covers ::resolve
     * @covers ::getTemplatePath
     * @covers ::__construct
     */
    public function testResolveTemplateMatcherReturnsPath(): void
    {
        $this->templatePathMatcher->match('exception.phtml')
            ->shouldBeCalledOnce()
            ->willReturn('/test');

        $this->assertSame('/test/exception.phtml', $this->object->resolve('exception.phtml'));
    }

    /**
     * @covers ::resolve
     * @covers ::getTemplatePath
     * @covers ::__construct
     */
    public function testResolveReturnsDefaultPath(): void
    {
        $this->templatePathMatcher->match('exception.phtml')
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->assertSame(
            realpath(__DIR__ . '/../../../templates/exception.phtml'),
            $this->object->resolve('exception.phtml')
        );
    }
}
