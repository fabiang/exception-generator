<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use Fabiang\ExceptionGenerator\Event\CreateExceptionEvent;
use Fabiang\ExceptionGenerator\Generator\ExceptionClassNames;
use Fabiang\ExceptionGenerator\Generator\TemplateRenderer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;

class CreateException
{
    protected TemplateRenderer $templateRenderer;
    protected EventDispatcherInterface $eventDispatcher;
    protected bool $overwrite = false;

    /**
     * for skipping confirmation to overwrite existing files
     */
    protected bool $skipAll = false;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        TemplateRenderer $templateRenderer,
        bool $overwrite = false
    ) {
        $this->templateRenderer = $templateRenderer;
        $this->eventDispatcher  = $eventDispatcher;
        $this->overwrite        = $overwrite;
    }

    /**
     * creates the exception classes and the exception folder
     */
    public function create(string $namespace, string $path, ?string $usePath = null): void
    {
        $exceptionNames = ExceptionClassNames::getExceptionClassNames();

        //create the dir for exception classes if not already exists
        $path .= '/';
        if (! is_dir($path)) {
            mkdir($path);
        }

        if (null !== $usePath) {
            $usePath .= '\\';
        }

        if ($this->overwrite) {
            $this->eventDispatcher->dispatch(new CreateExceptionEvent($path), 'overwrite.all');
        }

        foreach ($exceptionNames as $name) {
            $fileName = $path . $name . '.php';

            if ($this->validate($fileName)) {
                $specifiedUsePath = null !== $usePath ? $usePath . $name : null;
                $content          = $this->templateRenderer->render($namespace, $specifiedUsePath, $name);
                $event            = new CreateExceptionEvent($fileName);
                $this->eventDispatcher->dispatch($event, 'write.file');
                file_put_contents($fileName, $content);
            } else {
                $event = new CreateExceptionEvent($fileName);
                $this->eventDispatcher->dispatch($event, 'creation.skipped');
            }
        }

        $fileName = $path . 'ExceptionInterface.php';
        if ($this->validate($fileName)) {
            $specifiedUsePath = null !== $usePath ? $usePath . 'ExceptionInterface' : null;
            $content          = $this->templateRenderer->render($namespace, $specifiedUsePath);
            $event            = new CreateExceptionEvent($fileName);
            $this->eventDispatcher->dispatch($event, 'write.file');
            file_put_contents($fileName, $content);
        } else {
            $event = new CreateExceptionEvent($fileName);
            $this->eventDispatcher->dispatch($event, 'creation.skipped');
        }
    }

    /**
     * Check if file exists, and if so ask for overwrite confirmation
     */
    protected function validate(string $fileName): bool
    {
        $fileExists = is_file($fileName);

        // if user has set overwrite argument or file doesnt already exists return early
        if ($this->overwrite || ! $fileExists) {
            return true;
        }

        // if user has chosen to skip overwriting all existing files, then return early
        if ($this->skipAll) {
            return false;
        }

        $overwrite = false;
        $confirm   = $this->confirm($fileName);
        switch ($confirm) {
            case 'all':
                $this->overwrite = true;
                $overwrite       = true;
                $this->eventDispatcher->dispatch(new CreateExceptionEvent($fileName), 'overwrite.all');
                break;

            case 'yes':
                $overwrite = true;
                break;

            case 'nall':
                $this->skipAll = true;
                $overwrite     = false;
                $this->eventDispatcher->dispatch(new CreateExceptionEvent($fileName), 'skip.all');
                break;

            default:
                break;
        }

        return $overwrite;
    }

    /**
     * Ask for user confirmation.
     */
    protected function confirm(string $fileName): ?string
    {
        $event = new CreateExceptionEvent($fileName);
        $this->eventDispatcher->dispatch($event, 'overwrite.confirm');
        return $event->getConfirm();
    }

    /**
     * Set that create overwrites classes.
     */
    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }
}
