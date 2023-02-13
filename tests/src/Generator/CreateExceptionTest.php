<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use Fabiang\ExceptionGenerator\Event\CreateExceptionEvent;
use Fabiang\ExceptionGenerator\Generator\TemplateRenderer;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function array_shift;
use function file_get_contents;
use function in_array;
use function is_dir;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Generator\CreateException
 */
final class CreateExceptionTest extends TestCase
{
    use ProphecyTrait;

    private CreateException $object;
    private ObjectProphecy $eventDispatcher;
    private ObjectProphecy $templateRenderer;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->eventDispatcher  = $this->prophesize(EventDispatcherInterface::class);
        $this->templateRenderer = $this->prophesize(TemplateRenderer::class);

        $this->object = new CreateException($this->eventDispatcher->reveal(), $this->templateRenderer->reveal());
        vfsStream::setup('src', null, []);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Generator\ExceptionClassNames
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent
     *
     * @covers ::create
     * @covers ::__construct
     * @covers ::validate
     * @covers ::setOverwrite
     * @covers ::confirm
     * @dataProvider provideTestData
     */
    public function testCreate(
        ?string $confirm,
        bool $overwrite,
        bool $writeFiles,
        int $dispatchCount,
        int $templateCount,
        array $expectedEvents,
        array $expectedFileNames,
        string $content = ''
    ): void {
        $this->object->setOverwrite($overwrite);

        $path = vfsStream::url('src/exceptions');

        $knownClassNames            = ExceptionClassNames::getExceptionClassNames();
        $expectedPassedClassNames   = $knownClassNames;
        $expectedPassedClassNames[] = null;

        $files = [
            'BadMethodCallException.php'   => '',
            'DomainException.php'          => '',
            'InvalidArgumentException.php' => '',
            'LengthException.php'          => '',
            'LogicException.php'           => '',
            'OutOfBoundsException.php'     => '',
            'OutOfRangeException.php'      => '',
            'OverflowException.php'        => '',
            'RangeException.php'           => '',
            'RuntimeException.php'         => '',
            'UnderflowException.php'       => '',
            'UnexpectedValueException.php' => '',
            'ExceptionInterface.php'       => '',
        ];

        if ($writeFiles) {
            vfsStream::create(['exceptions' => $files]);
        }

        $expectedFileNamesIndex = 0;

        $this->templateRenderer->render(
            'testnamespace',
            null,
            Argument::that(function ($name) use (&$expectedPassedClassNames) {
                    $currentName = array_shift($expectedPassedClassNames);
                    return $name === $currentName;
            })
        )
            ->shouldBeCalledTimes($templateCount)
            ->willReturn($content);

        $this->eventDispatcher->dispatch(
            Argument::that(function (CreateExceptionEvent $event) use (
                $path,
                &$expectedFileNames,
                &$expectedFileNamesIndex
            ) {
                if (! isset($expectedFileNames[$expectedFileNamesIndex])) {
                    return true;
                }

                    $currentFile = $expectedFileNames[$expectedFileNamesIndex++];

                if (null === $currentFile) {
                    return true;
                }

                    $currentFileName = $path . '/' . $currentFile;
                    return $event->getFileName() === $currentFileName;
            }),
            Argument::that(function (string $eventName) use ($expectedEvents) {
                    return in_array($eventName, $expectedEvents);
            })
        )
            ->shouldBeCalledTimes($dispatchCount)
            ->will(function (array $args) use ($confirm) {
                $event     = $args[0];
                $eventName = $args[1];

                if ($eventName === 'overwrite.confirm') {
                    $event->setConfirm($confirm);
                }
                return $event;
            });

        $this->object->create('testnamespace', $path);
        $this->assertFileExists($path);
        $this->assertTrue(is_dir($path), 'Failed asserting that exception directory is an directory');

        foreach (ExceptionClassNames::getExceptionClassNames() as $className) {
            $fileName = $path . '/' . $className . '.php';
            $this->assertFileExists($fileName);
            $this->assertSame($content, file_get_contents($fileName));
        }
    }

    public static function provideTestData(): array
    {
        return [
            [
                'confirm'           => null,
                'overwrite'         => false,
                'writeFiles'        => false,
                'dispatchCount'     => 13,
                'templateCount'     => 13,
                'expectedEvents'    => ['write.file'],
                'expectedFileNames' => [],
                'content'           => 'testcontent',
            ],
            [
                'confirm'           => null,
                'overwrite'         => false,
                'writeFiles'        => true,
                'dispatchCount'     => 26,
                'templateCount'     => 0,
                'expectedEvents'    => ['overwrite.confirm', 'creation.skipped'],
                'expectedFileNames' => [
                    'BadMethodCallException.php',
                    'BadMethodCallException.php',
                    'DomainException.php',
                    'DomainException.php',
                    'InvalidArgumentException.php',
                    'InvalidArgumentException.php',
                    'LengthException.php',
                    'LengthException.php',
                    'LogicException.php',
                    'LogicException.php',
                    'OutOfBoundsException.php',
                    'OutOfBoundsException.php',
                    'OutOfRangeException.php',
                    'OutOfRangeException.php',
                    'OverflowException.php',
                    'OverflowException.php',
                    'RangeException.php',
                    'RangeException.php',
                    'RuntimeException.php',
                    'RuntimeException.php',
                    'UnderflowException.php',
                    'UnderflowException.php',
                    'UnexpectedValueException.php',
                    'UnexpectedValueException.php',
                    'ExceptionInterface.php',
                    'ExceptionInterface.php',
                ],
                'content'           => '',
            ],
            [
                'confirm'           => 'yes',
                'overwrite'         => false,
                'writeFiles'        => true,
                'dispatchCount'     => 26,
                'templateCount'     => 13,
                'expectedEvents'    => ['overwrite.confirm', 'write.file'],
                'expectedFileNames' => [
                    'BadMethodCallException.php',
                    'BadMethodCallException.php',
                    'DomainException.php',
                    'DomainException.php',
                    'InvalidArgumentException.php',
                    'InvalidArgumentException.php',
                    'LengthException.php',
                    'LengthException.php',
                    'LogicException.php',
                    'LogicException.php',
                    'OutOfBoundsException.php',
                    'OutOfBoundsException.php',
                    'OutOfRangeException.php',
                    'OutOfRangeException.php',
                    'OverflowException.php',
                    'OverflowException.php',
                    'RangeException.php',
                    'RangeException.php',
                    'RuntimeException.php',
                    'RuntimeException.php',
                    'UnderflowException.php',
                    'UnderflowException.php',
                    'UnexpectedValueException.php',
                    'UnexpectedValueException.php',
                    'ExceptionInterface.php',
                    'ExceptionInterface.php',
                ],
                'content'           => 'testcontent',
            ],
            [
                'confirm'           => null,
                'overwrite'         => true,
                'writeFiles'        => true,
                'dispatchCount'     => 14,
                'templateCount'     => 13,
                'expectedEvents'    => ['overwrite.all', 'write.file'],
                'expectedFileNames' => [
                    null,
                    'BadMethodCallException.php',
                    'DomainException.php',
                    'InvalidArgumentException.php',
                    'LengthException.php',
                    'LogicException.php',
                    'OutOfBoundsException.php',
                    'OutOfRangeException.php',
                    'OverflowException.php',
                    'RangeException.php',
                    'RuntimeException.php',
                    'UnderflowException.php',
                    'UnexpectedValueException.php',
                    'ExceptionInterface.php',
                ],
                'content'           => 'testcontent',
            ],
            [
                'confirm'           => 'all',
                'overwrite'         => false,
                'writeFiles'        => true,
                'dispatchCount'     => 15,
                'templateCount'     => 13,
                'expectedEvents'    => ['overwrite.all', 'overwrite.confirm', 'write.file'],
                'expectedFileNames' => [
                    null,
                    'BadMethodCallException.php',
                    'BadMethodCallException.php',
                    'DomainException.php',
                    'InvalidArgumentException.php',
                    'LengthException.php',
                    'LogicException.php',
                    'OutOfBoundsException.php',
                    'OutOfRangeException.php',
                    'OverflowException.php',
                    'RangeException.php',
                    'RuntimeException.php',
                    'UnderflowException.php',
                    'UnexpectedValueException.php',
                    'ExceptionInterface.php',
                ],
                'content'           => 'testcontent',
            ],
        ];
    }
}
