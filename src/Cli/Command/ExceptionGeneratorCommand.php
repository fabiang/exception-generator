<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Cli\Command;

use Fabiang\ExceptionGenerator\Generator\CreateException;
use Fabiang\ExceptionGenerator\Generator\RecursiveNamespaceResolver;
use Fabiang\ExceptionGenerator\Generator\RecursiveParentExceptionResolver;
use Fabiang\ExceptionGenerator\Generator\TemplateRenderer;
use Fabiang\ExceptionGenerator\Listener\CreateExceptionListener;
use Fabiang\ExceptionGenerator\TemplateResolver\TemplatePathMatcher;
use Fabiang\ExceptionGenerator\TemplateResolver\TemplateResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function array_reverse;
use function getcwd;
use function is_array;
use function realpath;
use function substr;

class ExceptionGeneratorCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('exception-generator')
            ->setDescription('Generates Exception Classes for php files in current dir.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Basepath for generating exception class.'
            )
            ->addOption(
                'overwrite',
                'o',
                InputOption::VALUE_NONE,
                'Force overwriting existing exception classes.'
            )
            ->addOption(
                'template-path',
                't',
                InputOption::VALUE_REQUIRED,
                'Set path for templates you want to use.'
            )
            ->addOption(
                'no-parents',
                'p',
                InputOption::VALUE_NONE,
                'Disable searching for parent exceptions.'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('path')) {
            $path = $this->realpath($input->getArgument('path'));
        } else {
            $path = getcwd();
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper  = $this->getHelper('question');
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new CreateExceptionListener($output, $input, $questionHelper));
        $namespaceResolver   = new RecursiveNamespaceResolver($eventDispatcher);
        $namespace           = $namespaceResolver->resolveNamespace($path);
        $templatePathMatcher = new TemplatePathMatcher($path, $this->getApplication()->getHome());

        $templatePath     = $this->realpath($input->getOption('template-path')) ?: null;
        $templateResolver = new TemplateResolver($templatePath, $templatePathMatcher);

        $exceptionTemplate = $templateResolver->resolve('exception.phtml');
        $interfaceTemplate = $templateResolver->resolve('interface.phtml');

        $useParents = $input->getOption('no-parents') ? false : true;

        $output->writeln('Using path for templates: ', OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Exception-Path: "' . $exceptionTemplate . '"', OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Interface-Path: "' . $interfaceTemplate . '"', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $templateRenderer = new TemplateRenderer();
        $templateRenderer->addPath('exception', $exceptionTemplate);
        $templateRenderer->addPath('interface', $interfaceTemplate);

        $parentExceptionNamespace = null;

        if (false !== $useParents) {
            $parentExceptionResolver = new RecursiveParentExceptionResolver($eventDispatcher);
            $parentExceptionDirs     = $parentExceptionResolver->resolveExceptionDirs($path);
            if (is_array($parentExceptionDirs)) {
                $parentExceptionDirs = array_reverse($parentExceptionDirs);
                foreach ($parentExceptionDirs as $parentExceptionDir) {
                    $prevParentNamespace      = $parentExceptionNamespace;
                    $parentExceptionNamespace = $namespaceResolver->resolveNamespace($parentExceptionDir);

                    $output->writeln(
                        'BaseExceptionPath: ' . $parentExceptionDir,
                        OutputInterface::VERBOSITY_VERY_VERBOSE
                    );
                    $output->writeln(
                        'BaseExceptionNamespace: ' . $parentExceptionNamespace,
                        OutputInterface::VERBOSITY_VERY_VERBOSE
                    );

                    $parentExceptionCreator = new CreateException(
                        $eventDispatcher,
                        $templateRenderer,
                        false,
                        $output,
                        $input
                    );
                    $parentExceptionCreator->create(
                        $parentExceptionNamespace,
                        $parentExceptionDir,
                        $prevParentNamespace
                    );
                }
            }
        }

        if (
            $parentExceptionNamespace && false === $useParents ||
            ($parentExceptionNamespace && false !== $useParents)
        ) {
            $output->writeln('BaseExceptionPath: not found/used', OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        $namespaceQuestion = new Question("Is this the correct namespace: [$namespace]?", $namespace);
        $inputNamespace    = $questionHelper->ask($input, $output, $namespaceQuestion);
        $output->writeln('Namespace set to "' . $inputNamespace . '"');

        $exceptionCreator = new CreateException(
            $eventDispatcher,
            $templateRenderer,
            $input->getOption('overwrite'),
            $output,
            $input
        );
        $exceptionCreator->create($inputNamespace, $path . '/Exception', $parentExceptionNamespace);

        return 0;
    }

    /**
     * Realpath.
     */
    protected function realpath(?string $path): string|bool
    {
        if (null === $path) {
            return '';
        }

        // extra check for virtual file system since vfsstream can't handle realpath()
        if (substr($path, 0, 6) === 'vfs://') {
            return $path;
        }

        return realpath($path);
    }
}
