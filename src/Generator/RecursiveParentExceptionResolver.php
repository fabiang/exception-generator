<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\BreakListener\GitDirectoryListener;
use Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener;
use Fabiang\ExceptionGenerator\DirLoopListener\ExceptionDirListener;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function basename;
use function count;
use function dirname;

class RecursiveParentExceptionResolver
{
    private const VFSSTREAM_PREFIX = 'vfs:';
    private const VFSSTREAM_URL    = self::VFSSTREAM_PREFIX . '//';

    /**
     * provides a namespace dpending on looped folders after searching for parent exceptions, which you should use
     */
    protected string $providedNamespace;

    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->registerDefaultListeners();
    }

    /**
     * Register default listeners
     */
    private function registerDefaultListeners(): void
    {
        $this->eventDispatcher->addSubscriber(new GitDirectoryListener());
        $this->eventDispatcher->addSubscriber(new RootDirectoryListener());
        $this->eventDispatcher->addSubscriber(new ExceptionDirListener());
    }

    /**
     * Returns an array containing arrays with parent exception folder and its namespace
     */
    public function resolveExceptionDirs(string $path): ?array
    {
        $exceptionDirArray = null;
        $eventDispatcher   = $this->eventDispatcher;
        $loopedPaths[]     = basename($path);
        $path              = dirname($path);

        // loop as long a break listener doesn't stop propagation or we have empty directories
        // we iterate through directories up
        do {
            $directory = $this->getDirectoryContents($path);

            // loop over files/directories and check if the listener can find an exception directory
            foreach ($directory as $item) {
                $exceptionDirectoryEvent = new FileEvent($item);
                $eventDispatcher->dispatch($exceptionDirectoryEvent, 'dir.loop');
                //break early, cuz one exception directory can only appear once
                if ($exceptionDirectoryEvent->isPropagationStopped()) {
                    $exceptionDirArray[] = $exceptionDirectoryEvent->getParentExceptionDir();
                    break;
                }
            }

            // check for listeners that check if the path iteration loop should be stopped
            foreach ($directory as $item) {
                $breakEvent = new FileEvent($item);
                $eventDispatcher->dispatch($breakEvent, 'file.break');
                if (false !== $breakEvent->isPropagationStopped()) {
                    break 2;
                }
            }

            $path          = dirname($path) !== static::VFSSTREAM_PREFIX ? dirname($path) : static::VFSSTREAM_URL;
            $loopedPaths[] = basename($path);
            //break early cuz DirectoryIterator can't handle vfs root folder
        } while ((0 === count($directory) || ! $breakEvent->isPropagationStopped()) && $path !== static::VFSSTREAM_URL);

        return $exceptionDirArray;
    }

    /**
     * Get directory contents without dot files.
     *
     * @return array<int, DirectoryIterator>
     */
    private function getDirectoryContents(string $path): iterable
    {
        $directory = new DirectoryIterator($path);
        $items     = [];
        foreach ($directory as $item) {
            if (! $item->isDot()) {
                $items[] = clone $item;
            }
        }
        return $items;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
