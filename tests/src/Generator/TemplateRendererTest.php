<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\ResolverInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Generator\TemplateRenderer
 */
final class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $object;
    private MockObject $resolver;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $templatePath   = vfsStream::url('test/templates');
        $this->resolver = $this->createMock(ResolverInterface::class);
        $this->resolver->expects($this->any())
            ->method('resolve')
            ->willReturnCallback(function ($name) use ($templatePath) {
                return $templatePath . '/' . $name . '.phtml';
            });

        $renderer = new PhpRenderer();
        $renderer->setResolver($this->resolver);
        vfsStream::setup('test', null, ['templates' => []]);
        $this->object = new TemplateRenderer();
        $this->object->addPath('exception', vfsStream::url('test/templates/exception.phtml'));
        $this->object->addPath('interface', vfsStream::url('test/templates/interface.phtml'));
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Generator\TemplateRenderer::addPath
     *
     * @covers ::render
     * @covers ::__construct
     * @dataProvider renderTestTemplate
     */
    public function testRender(
        string $template,
        string $templateName,
        string $namespace,
        ?string $exceptionName,
        string $renderedFile
    ): void {
        $path = vfsStream::url('test/templates/' . $templateName . '.phtml');
        file_put_contents($path, $template);
        $this->assertSame($renderedFile, $this->object->render($namespace, null, $exceptionName));
    }

    public static function renderTestTemplate(): array
    {
        return [
            [
                'template'      => '<?php echo $namespace ?>',
                'templateName'  => 'interface',
                'namespace'     => 'foo\bar',
                'exceptionName' => null,
                'renderedFile'  => 'foo\bar',
            ],
            [
                'template'      => '<?php echo $namespace ?> - <?php echo $exceptionName ?>',
                'templateName'  => 'exception',
                'namespace'     => 'foo\bar',
                'exceptionName' => 'Test',
                'renderedFile'  => 'foo\bar - Test',
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getRenderer
     * @covers ::addPath
     */
    public function testZFRendererGetTemplatePathPassed(): void
    {
        $templateException = vfsStream::url('test/templates/exception.phtml');

        $renderer = new PhpRenderer();
        $object   = new TemplateRenderer($renderer);
        $object->addPath('exception', $templateException);

        $this->assertInstanceOf(PhpRenderer::class, $object->getRenderer());
        $this->assertSame($templateException, $object->getRenderer()->resolver()->resolve('exception'));
    }
}
