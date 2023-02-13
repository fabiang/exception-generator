<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Listener;

use Fabiang\ExceptionGenerator\Event\CreateExceptionEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateExceptionListener implements EventSubscriberInterface
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected QuestionHelper $question;

    public function __construct(OutputInterface $output, InputInterface $input, QuestionHelper $question)
    {
        $this->output   = $output;
        $this->input    = $input;
        $this->question = $question;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'creation.skipped'  => ['onSkippedCreation'],
            'overwrite.all'     => ['onOverwriteAll'],
            'skip.all'          => ['onSkipOverwriteAll'],
            'write.file'        => ['onWriteFile'],
            'overwrite.confirm' => ['onOverwriteConfirm'],
        ];
    }

    /**
     * File writing was skipped event.
     */
    public function onSkippedCreation(CreateExceptionEvent $event): void
    {
        $this->output->writeln('Skipped creating "' . $event->getFileName() . '"');
    }

    /**
     * Overwriting of all files event.
     */
    public function onOverwriteAll(): void
    {
        $this->output->writeln('Overwriting all existing files!');
    }

    /**
     * Skip confirmation to overwrite existing files event.
     */
    public function onSkipOverwriteAll(): void
    {
        $this->output->writeln('Skipped overwriting all existing files.');
    }

    /**
     * Overwriting of a single file event.
     */
    public function onWriteFile(CreateExceptionEvent $event): void
    {
        $message = ' "' . $event->getFileName() . '"...';
        if ($event->fileExists()) {
            $message = 'Overwriting' . $message;
        } else {
            $message = 'Writing' . $message;
        }

        $this->output->writeln($message);
    }

    /**
     * Event for asking the user of confirmation to overwrite a file
     */
    public function onOverwriteConfirm(CreateExceptionEvent $event): void
    {
        $question = new ChoiceQuestion(
            'File [' . $event->getFileName() . '] already exists, overwrite?',
            ['y' => 'yes', 'n' => 'no', 'all' => 'all', 'nall' => 'nall'],
            'n'
        );

        $confirm = $this->question->ask(
            $this->input,
            $this->output,
            $question
        );

        $event->setConfirm($confirm);
    }
}
