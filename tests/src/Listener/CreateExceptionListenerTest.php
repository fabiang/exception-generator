<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Listener;

use Fabiang\ExceptionGenerator\Event\CreateExceptionEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

use function array_shift;
use function get_class;
use function method_exists;
use function strpos;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Listener\CreateExceptionListener
 */
final class CreateExceptionListenerTest extends TestCase
{
    private CreateExceptionListener $object;
    private MockObject $output;
    private MockObject $input;
    private MockObject $question;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->output   = $this->createMock(OutputInterface::class);
        $this->input    = $this->createMock(InputInterface::class);
        $this->question = $this->createMock(QuestionHelper::class);
        $this->object   = new CreateExceptionListener($this->output, $this->input, $this->question);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Listener\CreateExceptionListener::__construct
     *
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $events    = $this->object->getSubscribedEvents();
        $className = get_class($this->object);
        foreach ($events as $event => $listenerMethod) {
            $method = array_shift($listenerMethod);
            $this->assertTrue(
                method_exists($this->object, $method),
                "Method \"$method\" doesn't exist in class "
                    . "\"$className\" but is defined as callback for event \"$event\""
            );
        }
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent
     *
     * @covers ::onSkippedCreation
     * @covers ::__construct
     */
    public function testOnSkippedCreation(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Skipped creating "testfilename"'));

        $event = new CreateExceptionEvent('testfilename');
        $this->object->onSkippedCreation($event);
    }

    /**
     * @covers ::onOverwriteAll
     * @covers ::__construct
     */
    public function testOnOverwriteAll(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Overwriting all existing files!'));

        $this->object->onOverwriteAll(new CreateExceptionEvent('testfilename'));
    }

    /**
     * @covers ::onWriteFile
     * @covers ::__construct
     */
    public function testOnWriteFileFileDoesntExist(): void
    {
        $event = $this->createMock(CreateExceptionEvent::class);

        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Writing "testfilename"...'));

        $event->expects($this->once())
            ->method('getFileName')
            ->willReturn('testfilename');

        $event->expects($this->once())
            ->method('fileExists')
            ->willReturn(false);

        $this->object->onWriteFile($event);
    }

    /**
     * @covers ::onWriteFile
     * @covers ::__construct
     */
    public function testOnWriteFileFileDoesExist(): void
    {
        $event = $this->createMock(CreateExceptionEvent::class);

        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Overwriting "testfilename"...'));

        $event->expects($this->once())
            ->method('getFileName')
            ->willReturn('testfilename');

        $event->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $this->object->onWriteFile($event);
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent
     *
     * @covers ::onOverwriteConfirm
     * @covers ::__construct
     */
    public function testOnOverwriteConfirm(): void
    {
        $this->question->expects($this->once())
            ->method('ask')
            ->with(
                $this->equalTo($this->input),
                $this->equalTo($this->output),
                $this->callback(function (ChoiceQuestion $object) {
                    return strpos($object->getQuestion(), 'testfilename') !== false;
                })
            )
            ->willReturn('y');

        $event = new CreateExceptionEvent('testfilename');
        $this->object->onOverwriteConfirm($event);
        $this->assertSame('y', $event->getConfirm());
    }
}
