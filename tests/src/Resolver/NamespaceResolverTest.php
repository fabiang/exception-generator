<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Resolver;

use Fabiang\ExceptionGenerator\Exception\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_put_contents;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Resolver\NamespaceResolver
 */
final class NamespaceResolverTest extends TestCase
{
    private NamespaceResolver $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new NamespaceResolver();
        vfsStream::setup('src');
    }

    /**
     * @covers ::resolve
     * @dataProvider provideTestPHPClasses
     */
    public function testResolve(string $source, string|false $namespace): void
    {
        $path = vfsStream::url('src/test.php');
        file_put_contents($path, $source);
        $this->assertSame($namespace, $this->object->resolve($path, []));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithLoopedDirecotries(): void
    {
        $path = vfsStream::url('src/test.php');
        file_put_contents($path, '<?php namespace \Test\Foo\Bar;');
        $this->assertSame(
            'Test\Foo\Bar\Test\Subpath',
            $this->object->resolve($path, ['Subpath', 'Test'])
        );
    }

    public static function provideTestPHPClasses(): array
    {
        return [
            [
                'source'    => '',
                'namespace' => false,
            ],
            [
                'source'    => '<?php class Foo {}',
                'namespace' => false,
            ],
            [
                'source'    => '<?php',
                'namespace' => false,
            ],
            [
                'source'    => '<?php namespace \Test\Foo\Bar;',
                'namespace' => 'Test\Foo\Bar',
            ],
            [
                'source'    => '<?php namespace  Test\Bar\Baz;',
                'namespace' => 'Test\Bar\Baz',
            ],
            [
                'source'    => "<?php namespace\nTest\Burntomi;",
                'namespace' => 'Test\Burntomi',
            ],
            [
                'source'    => "<?php namespace Test\Fabian\n;",
                'namespace' => 'Test\Fabian',
            ],
            [
                'source'    => "<?php namespace Test\SemiMissing\n\nclass Foo{}",
                'namespace' => false,
            ],
            [
                'source'    => "<?php namespace Test\ Fabian\n;",
                'namespace' => 'Test\Fabian',
            ],
            [
                'source'    => "<?php ; namespace Test\ Fabian\n;",
                'namespace' => 'Test\Fabian',
            ],
            [
                'source'    => "<?php ; namespace ;",
                'namespace' => '',
            ],
            [
                'source'    => "<?php ; namespace Test\\Fabian\n;",
                'namespace' => 'Test\\Fabian',
            ],
            [
                'source'    => "<?php ; namespace Test\F\n;",
                'namespace' => 'Test\\F',
            ],
            [
                'source'    => "<?php namespace Test\F\n use Symfony\Component\Console\Command\Command
                                        use Symfony\Component\Console\Input\InputArgument
                                        protected function configure()\n{
                                        const T_WHITESPACE   = T_WHITESPACE}",
                'namespace' => false,
            ],
        ];
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFileDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP file "/phpunit-missing" isn\'t readable');

        $this->object->resolve('/phpunit-missing', []);
    }

    /**
     * @covers ::resolve
     * @requires PHP 5.4
     */
    public function testResolveFileIsNotReadable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP file "vfs://src/test.php" isn\'t readable');

        $path = vfsStream::url('src/test.php');
        file_put_contents($path, '<?php');
        chmod($path, 0000);
        $this->object->resolve($path, []);
    }
}
