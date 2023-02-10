<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use Fabiang\ExceptionGenerator\BreakListener\GitDirectoryListener;
use Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener;
use Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function dirname;
use function get_class;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver
 */
final class RecursiveNamespaceResolverTest extends TestCase
{
    private RecursiveNamespaceResolver $object;
    private MockObject $eventDispatcher;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->object          = new RecursiveNamespaceResolver($this->eventDispatcher);
        vfsStream::setup('namespace-resolver', null, ['subdir' => []]);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener::__construct
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::registerDefaultListeners
     *
     * @covers ::resolveNamespace
     * @covers ::getDirectoryContents
     */
    public function testResolveNamespaceEmptyDirectory(): void
    {
        $this->eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->will($this->returnCallback(function (FileEvent $event, $eventName) {
                if ($eventName === 'file.break') {
                    $event->stopPropagation();
                }
                return $event;
            }));
        $this->assertNull($this->object->resolveNamespace(vfsStream::url('namespace-resolver/subdir')));
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener::__construct
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::registerDefaultListeners
     *
     * @covers ::resolveNamespace
     * @covers ::getDirectoryContents
     */
    public function testResolveNamespaceFoundNamespaceByAListener(): void
    {
        $this->eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->will($this->returnCallback(function (FileEvent $event, $eventName) {
                if ($eventName === 'file.loop') {
                    $event->setNamespace('MyNameSpaceTest');
                }
                return $event;
            }));

        $this->assertSame(
            'MyNameSpaceTest',
            $this->object->resolveNamespace(vfsStream::url('namespace-resolver/subdir'))
        );
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\FileEvent
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener::__construct
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::__construct
     * @uses Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver::registerDefaultListeners
     *
     * @covers ::resolveNamespace
     * @covers ::getDirectoryContents
     */
    public function testResolveNamespaceFoundNamespaceByAListenerWhichStoppsPropagation(): void
    {
        $this->eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->will($this->returnCallback(function (FileEvent $event, $eventName) {
                if ($eventName === 'file.loop') {
                    $event->stopPropagation();
                    $event->setNamespace('MyNameSpaceTest');
                }
                return $event;
            }));

        $this->assertSame(
            'MyNameSpaceTest',
            $this->object->resolveNamespace(vfsStream::url('namespace-resolver/subdir'))
        );
    }

    /**
     * @coversNothing
     * @dataProvider provideTestDirectories
     * @group integration
     */
    public function testResolveNamespace(array $structure, string $path, ?string $expected): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener('file.break', function (FileEvent $event) {
            $dirname = dirname($event->getFile());
            if (vfsStream::url('namespace-resolver') === $dirname) {
                $event->stopPropagation();
            }
        });
        $object = new RecursiveNamespaceResolver($eventDispatcher);
        vfsStream::setup('namespace-resolver', null, $structure);
        $this->assertSame($expected, $object->resolveNamespace($path));
    }

    public static function provideTestDirectories(): array
    {
        return [
            [
                'structure' => [
                    'subdir' => [
                        'Test.php' => '<?php namespace Foobar; class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'Foobar',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'Test.php' => '<?php namespace Foobar class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => null,
            ],
            [
                'structure' => [
                    'subdir' => [
                        'Fail.php'    => '<?php namespace Fail class Test{}',
                        'Success.php' => '<?php namespace SuccessAfterFail; class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'SuccessAfterFail',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'composer.json' => '{"autoload":{"psr-4":{"Fabiang\\\\ExceptionGenerator1\\\\":"src/"}}}',
                        'Success.php'   => '<?php namespace IgnoredJson; class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'IgnoredJson',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'composer.json' => '{"autoloat":{"psr-4":{"Fabiang\\\\ExceptionGenerator1\\\\":"src/"}}}',
                        'Success.php'   => '<?php namespace SuccessAfterFailComposer; class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'SuccessAfterFailComposer',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'Test.php'  => '<?php namespace Foobar; class Test{}',
                        'foobarbaz' => ['.git' => []],
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/foobarbaz'),
                'expected'  => null,
            ],
            [
                'structure' => [
                    'subdir' => [
                        'Test.php' => '<?php namespace Foobar; class Test{}',
                        '.git'     => [],
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'Foobar',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'composer.json' => '{"autoload":{"psr-4":{"Fabiang\\\\ExceptionGenerator1\\\\":"src/"}}}',
                        'Success.php'   => '<?php namespace MissingSemicolon class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'Fabiang\ExceptionGenerator1',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'foobarbaz' => ['Test.php' => '<?php namespace Foobar; class Test{}'],
                        ['.git' => []],
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/foobarbaz'),
                'expected'  => 'Foobar',
            ],
            [
                'structure' => [
                    'subdir' => [
                        'foobarbaz' => ['Test.php' => '<?php namespace Foobar class Test{}'],
                        ['.git' => []],
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/foobarbaz'),
                'expected'  => null,
            ],
            [
                'structure' => [
                    'subdir' => [
                        'Fail.php'      => '<?php namespace Fail class Test{}',
                        'composer.json' => '{"autoload":{"psr-4":{"Fabiang\\\\ExceptionGenerator1\\\\":"src/"}}}',
                        'Success.php'   => '<?php namespace SuccessAfterFail class Test{}',
                    ],
                ],
                'path'      => vfsStream::url('namespace-resolver/subdir/'),
                'expected'  => 'Fabiang\ExceptionGenerator1',
            ],
        ];
    }

    /**
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener::__construct
     * @uses Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener::__construct
     *
     * @covers ::getEventDispatcher
     * @covers ::__construct
     * @covers ::registerDefaultListeners
     */
    public function testRegisterDefaultListeners(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $eventDispatcher->expects($this->exactly(4))
            ->method('addSubscriber')
            ->willReturnCallback(fn (object $instance) => match (get_class($instance)) {
                PHPFileListener::class => true,
                ComposerJsonListener::class => true,
                GitDirectoryListener::class => true,
                RootDirectoryListener::class => true,
                default => throw new LogicException()
            });

        $object = new RecursiveNamespaceResolver($eventDispatcher);
        $this->assertSame($eventDispatcher, $object->getEventDispatcher());
    }
}
