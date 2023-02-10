<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Resolver;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

use function file_put_contents;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Resolver\ComposerResolver
 */
final class ComposerResolverTest extends TestCase
{
    private ComposerResolver $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ComposerResolver();
        vfsStream::setup('src');
    }

    /**
     * @covers ::resolve
     * @dataProvider provideTestComposerJson
     */
    public function testResolve(string $source, string|false $namespace): void
    {
        $path = vfsStream::url('src/composer.json');
        file_put_contents($path, $source);
        $this->assertSame($namespace, $this->object->resolve($path, []));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithLoopedDirectories(): void
    {
        $path = vfsStream::url('src/composer.json');
        file_put_contents(
            $path,
            '{"autoload":{"psr-4":{"Fabiang\\\\ExceptionGenerator\\\\":"src/"}}}'
        );
        $this->assertSame(
            "Fabiang\ExceptionGenerator\Foo\Bar",
            $this->object->resolve($path, ['Bar', 'Foo', 'ExceptionGenerator', 'Fabiang', 'src'])
        );
    }

    public static function provideTestComposerJson(): array
    {
        return [
            [
                'source'    => '{"autoload":{"psr-4":{"Fabiang\\\\ExceptionGenerator1\\\\":"src/"}}}',
                'namespace' => "Fabiang\ExceptionGenerator1",
            ],
            [
                'source'    => '{"autoload":{"psr-0":{"Fabiang\\\\ExceptionGenerator2\\\\":"src/"}}}',
                'namespace' => "Fabiang\ExceptionGenerator2",
            ],
            [
                'source'    => '{"autoload":{"psr-1":{"Fabiang\\\\ExceptionGenerator3\\\\":"src/"}}}',
                'namespace' => false,
            ],
            [
                'source'    => '{"autoload":{"psr-2":{"Fabiang\\\\ExceptionGenerator4\\\\":"src/"}}}',
                'namespace' => false,
            ],
            [
                'source'    => '{"autoloat":{"psr-2":{"Fabiang\\\\ExceptionGenerator4\\\\":"src/"}}}',
                'namespace' => false,
            ],
            [
                'source'    => '"autoloat":{"psr-2":{"Fabiang\\\\ExceptionGenerator4\\\\":"src/"}}',
                'namespace' => false,
            ],
            [
                'source'    => '{"autoload":{"psr-4":{"Fabiang\ExceptionGenerator1\":"src/"}}}',
                'namespace' => false,
            ],
            [
                'source'    => '',
                'namespace' => false,
            ],
            [
                'source'    => '{"autoload": {"psr-4": {"Fabiang\\\\ExceptionGenerator1\\\\": "src/",
                                                          "Fabiang\\\\ExceptionGenerator2\\\\": "src/"
                                                          }}}',
                'namespace' => "Fabiang\ExceptionGenerator1",
            ],
            [
                'source'    => '{"autoload": {"psr-4": {"Fabiang\\ExceptionGenerator1\\": "src/",
                                                          "Fabiang\\\\ExceptionGenerator2\\\\": "src/"
                                                          }}}',
                'namespace' => false,
            ],
        ];
    }
}
