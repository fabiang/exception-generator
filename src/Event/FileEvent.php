<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Event;

use DirectoryIterator;
use Symfony\Contracts\EventDispatcher\Event;

class FileEvent extends Event
{
    /**
     * Namespace.
     */
    protected ?string $namespace = null;

    /**
     * ParentExceptionDir.
     */
    protected ?string $parentExceptionDir = null;

    /**
     * Full file path.
     */
    protected string $file;

    /**
     * File extension.
     */
    protected string $extension;

    /**
     * basename of item.
     */
    protected string $basename;

    /**
     * dirname of item.
     */
    protected string $dirname;

    /**
     * Item is an directory.
     */
    protected bool $isDir = false;

    /**
     * Cache of looped directories.
     */
    protected array $loopedDirectories = [];

    public function __construct(DirectoryIterator $file)
    {
        $this->file      = $file->getPathname();
        $this->extension = $this->getFileExtension($file);
        $this->basename  = $file->getBasename();
        $this->dirname   = $file->getPath();
        $this->isDir     = $file->isDir();
    }

    /**
     * Get found parentExceptionDirs
     */
    public function getParentExceptionDir(): ?string
    {
        return $this->parentExceptionDir;
    }

    /**
     * Set found parentExceptionDirs.
     */
    public function setParentExceptionDir(): void
    {
        $this->parentExceptionDir = $this->dirname . '/Exception';
    }

    /**
     * Get found namespace
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Set found namespace.
     */
    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Get full filename.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Get file extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Get basename of item.
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * Get dirname of item.
     */
    public function getDirname(): string
    {
        return $this->dirname;
    }

    /**
     * Is item an directory.
     */
    public function isDir(): bool
    {
        return $this->isDir;
    }

    /**
     * Compatiblity method for PHP versions not
     * supporting DirectoryIterator::getExtension
     */
    private function getFileExtension(DirectoryIterator $file): string
    {
        return $file->getExtension();
    }

    /**
     * Get looped directories while iterating up path.
     */
    public function getLoopedDirectories(): array
    {
        return $this->loopedDirectories;
    }

    /**
     * Set looped directories while iterating up path.
     */
    public function setLoopedDirectories(array $loopedDirectories): void
    {
        $this->loopedDirectories = $loopedDirectories;
    }
}
