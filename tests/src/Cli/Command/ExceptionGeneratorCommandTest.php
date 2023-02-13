<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Cli\Command;

use Fabiang\ExceptionGenerator\Cli\Console\Application;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Cli\Command\ExceptionGeneratorCommand
 */
class ExceptionGeneratorCommandTest extends TestCase
{
    use ProphecyTrait;

    private ExceptionGeneratorCommand $command;

    protected function setUp(): void
    {
        vfsStream::setup(
            'cwd',
            null,
            [
                '.git'          => [],
                'src'           => [
                    'Test.php'  => '<?php namespace Test;',
                    'Exception' => [],
                    'Parent'    => [
                        'Test.php' => '<?php namespace Test\Parent;',
                    ],
                ],
                'template_path' => ['exception.phtml' => '1', 'interface.phtml' => '2'],
            ]
        );
        $this->command = new ExceptionGeneratorCommand();
    }

    /**
     * @test
     * @covers ::execute
     * @covers ::realpath
     */
    public function executeNoParents(): void
    {
        $input  = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $input->isInteractive()->willReturn(true);

        $input->hasArgument('command')->willReturn(true);
        $input->getArgument('command')->willReturn('exception-generator');

        $input->hasArgument('path')->willReturn(true);
        $input->getArgument('path')->willReturn(vfsStream::url('cwd/src/Parent'));

        $input->getOption('overwrite')->willReturn(false);
        $input->getOption('no-parents')->willReturn(true);
        $input->getOption('template-path')->willReturn(vfsStream::url('cwd/template_path'));

        $input->bind(Argument::type(InputDefinition::class))->willReturn(null);
        $input->validate()->willReturn(null);

        $output->writeln(
            'Using path for templates: "vfs://cwd/template_path"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )->shouldBeCalled();
        $output->writeln(
            'Exception-Path: "vfs://cwd/template_path/exception.phtml"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )->shouldBeCalled();
        $output->writeln(
            'Interface-Path: "vfs://cwd/template_path/interface.phtml"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )
            ->shouldBeCalled();
        $output->writeln('Namespace set to "mynamespace"')->shouldBeCalled();

        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/BadMethodCallException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/DomainException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/InvalidArgumentException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/LengthException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/LogicException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OutOfBoundsException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OutOfRangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OverflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/RangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/RuntimeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/UnderflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/UnexpectedValueException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/ExceptionInterface.php"...')->shouldBeCalled();

        $question = $this->prophesize(QuestionHelper::class);
        $question->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::type(Question::class)
        )
            ->shouldBeCalled()
            ->willReturn('mynamespace');

        $helperSet = $this->prophesize(HelperSet::class);
        $helperSet->get('question')->willReturn($question->reveal());

        $this->command->setHelperSet($helperSet->reveal());

        $inputDefinition = $this->prophesize(InputDefinition::class);
        $inputDefinition->getOptions()->willReturn([]);
        $inputDefinition->getArguments()->willReturn([]);

        $app = $this->prophesize(Application::class);
        $app->getHome()->willReturn('/home/phpunit');
        $app->getHelperSet()->willReturn($helperSet);
        $app->getDefinition()->willReturn($inputDefinition->reveal());
        $this->command->setApplication($app->reveal());

        $this->assertSame(0, $this->command->run($input->reveal(), $output->reveal()));
    }

    /**
     * @test
     * @covers ::execute
     * @covers ::realpath
     */
    public function executeParents(): void
    {
        $input  = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $input->isInteractive()->willReturn(true);

        $input->hasArgument('command')->willReturn(true);
        $input->getArgument('command')->willReturn('exception-generator');

        $input->hasArgument('path')->willReturn(true);
        $input->getArgument('path')->willReturn(vfsStream::url('cwd/src/Parent'));

        $input->getOption('overwrite')->willReturn(false);
        $input->getOption('no-parents')->willReturn(false);
        $input->getOption('template-path')->willReturn(vfsStream::url('cwd/template_path'));

        $input->bind(Argument::type(InputDefinition::class))->willReturn(null);
        $input->validate()->willReturn(null);

        $output->writeln(
            'Using path for templates: "vfs://cwd/template_path"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )->shouldBeCalled();
        $output->writeln(
            'Exception-Path: "vfs://cwd/template_path/exception.phtml"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )->shouldBeCalled();
        $output->writeln(
            'Interface-Path: "vfs://cwd/template_path/interface.phtml"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )
            ->shouldBeCalled();
        $output->writeln('Namespace set to "mynamespace"')->shouldBeCalled();

        $output->writeln(
            'BaseExceptionPath: "vfs://cwd/src/Exception"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )
            ->shouldBeCalled();

        $output->writeln(
            'BaseExceptionNamespace: "Test\Exception"',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )
            ->shouldBeCalled();

        $output->writeln(
            'BaseExceptionPath: not found/used',
            OutputInterface::VERBOSITY_VERY_VERBOSE
        )
            ->shouldBeCalled();

        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/BadMethodCallException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/DomainException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/InvalidArgumentException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/LengthException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/LogicException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OutOfBoundsException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OutOfRangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/OverflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/RangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/RuntimeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/UnderflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/UnexpectedValueException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Parent/Exception/ExceptionInterface.php"...')->shouldBeCalled();

        $output->writeln('Writing "vfs://cwd/src/Exception/BadMethodCallException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/DomainException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/InvalidArgumentException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/LengthException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/LogicException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/OutOfBoundsException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/OutOfRangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/OverflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/RangeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/RuntimeException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/UnderflowException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/UnexpectedValueException.php"...')->shouldBeCalled();
        $output->writeln('Writing "vfs://cwd/src/Exception/ExceptionInterface.php"...')->shouldBeCalled();

        $question = $this->prophesize(QuestionHelper::class);
        $question->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::type(Question::class)
        )
            ->shouldBeCalled()
            ->willReturn('mynamespace');

        $helperSet = $this->prophesize(HelperSet::class);
        $helperSet->get('question')->willReturn($question->reveal());

        $this->command->setHelperSet($helperSet->reveal());

        $inputDefinition = $this->prophesize(InputDefinition::class);
        $inputDefinition->getOptions()->willReturn([]);
        $inputDefinition->getArguments()->willReturn([]);

        $app = $this->prophesize(Application::class);
        $app->getHome()->willReturn('/home/phpunit');
        $app->getHelperSet()->willReturn($helperSet);
        $app->getDefinition()->willReturn($inputDefinition->reveal());
        $this->command->setApplication($app->reveal());

        $this->assertSame(0, $this->command->run($input->reveal(), $output->reveal()));
    }
}
