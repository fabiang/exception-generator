<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Listener;

use Fabiang\ExceptionGenerator\Event\CreateExceptionEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
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
    use ProphecyTrait;

    private CreateExceptionListener $object;
    private ObjectProphecy $output;
    private ObjectProphecy $input;
    private ObjectProphecy $question;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->output   = $this->prophesize(OutputInterface::class);
        $this->input    = $this->prophesize(InputInterface::class);
        $this->question = $this->prophesize(QuestionHelper::class);

        $this->object = new CreateExceptionListener(
            $this->output->reveal(),
            $this->input->reveal(),
            $this->question->reveal()
        );
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
        $this->output->writeln('Skipped creating "testfilename"')
            ->shouldBeCalledOnce();

        $event = new CreateExceptionEvent('testfilename');
        $this->object->onSkippedCreation($event);
    }

    /**
     * @covers ::onOverwriteAll
     * @covers ::__construct
     */
    public function testOnOverwriteAll(): void
    {
        $this->output->writeln('Overwriting all existing files!')
            ->shouldBeCalledOnce();

        $this->object->onOverwriteAll(new CreateExceptionEvent('testfilename'));
    }

    /**
     * @covers ::onWriteFile
     * @covers ::__construct
     */
    public function testOnWriteFileFileDoesntExist(): void
    {
        $event = $this->prophesize(CreateExceptionEvent::class);

        $this->output->writeln('Writing "testfilename"...')
            ->shouldBeCalledOnce();

        $event->getFileName()
            ->shouldBeCalledOnce()
            ->willReturn('testfilename');

        $event->fileExists()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->object->onWriteFile($event->reveal());
    }

    /**
     * @covers ::onWriteFile
     * @covers ::__construct
     */
    public function testOnWriteFileFileDoesExist(): void
    {
        $event = $this->prophesize(CreateExceptionEvent::class);

        $this->output->writeln('Overwriting "testfilename"...')
            ->shouldBeCalledOnce();

        $event->getFileName()
            ->shouldBeCalledOnce()
            ->willReturn('testfilename');

        $event->fileExists()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->object->onWriteFile($event->reveal());
    }

    /**
     * @uses Fabiang\ExceptionGenerator\Event\CreateExceptionEvent
     *
     * @covers ::onOverwriteConfirm
     * @covers ::__construct
     */
    public function testOnOverwriteConfirm(): void
    {
        $this->question->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::that(function (ChoiceQuestion $object) {
                    return strpos($object->getQuestion(), 'testfilename') !== false;
            })
        )
            ->shouldBeCalledOnce()
            ->willReturn('y');

        $event = new CreateExceptionEvent('testfilename');
        $this->object->onOverwriteConfirm($event);
        $this->assertSame('y', $event->getConfirm());
    }
}
