<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Event;

use Symfony\Contracts\EventDispatcher\Event;

use function is_file;

class CreateExceptionEvent extends Event
{
    protected string $fileName;
    protected bool $fileExists = false;
    protected ?string $confirm = null;

    public function __construct(string $fileName)
    {
        $this->fileName   = $fileName;
        $this->fileExists = is_file($fileName);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Get confirmation value.
     */
    public function getConfirm(): ?string
    {
        return $this->confirm;
    }

    /**
     * Set confirmation value.
     */
    public function setConfirm(?string $confirm): void
    {
        $this->confirm = $confirm;
    }

    /**
     * Does file exist.
     */
    public function fileExists(): bool
    {
        return $this->fileExists;
    }
}
