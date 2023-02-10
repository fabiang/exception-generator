<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Cli\Console;

use Fabiang\ExceptionGenerator\Cli\Command\ExceptionGeneratorCommand;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

use function getenv;

final class Application extends ConsoleApplication
{
    /**
     * Home directory.
     */
    protected string $home;

    /**
     * {@inheritDoc}
     */
    protected function getCommandName(InputInterface $input): string
    {
        return 'exception-generator';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultCommands(): array
    {
        $commands   = parent::getDefaultCommands();
        $commands[] = new ExceptionGeneratorCommand();
        return $commands;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();
        return $inputDefinition;
    }

    /**
     * Get path to home directory.
     *
     * @return string
     */
    public function getHome()
    {
        if (null === $this->home) {
            $this->home = getenv('HOME');
        }

        return $this->home;
    }

    /**
     * Set path to home directory.
     *
     * @param string $home
     */
    public function setHome($home)
    {
        $this->home = (string) $home;
    }
}
