<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use DirectoryIterator;
use Fabiang\ExceptionGenerator\BreakListener\GitDirectoryListener;
use Fabiang\ExceptionGenerator\BreakListener\RootDirectoryListener;
use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\FileLoopListener\ComposerJsonListener;
use Fabiang\ExceptionGenerator\FileLoopListener\PHPFileListener;
use Fabiang\ExceptionGenerator\Resolver\ComposerResolver;
use Fabiang\ExceptionGenerator\Resolver\NamespaceResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function basename;
use function count;
use function dirname;

class RecursiveNamespaceResolver
{
    public function __construct(protected EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->registerDefaultListeners();
    }

    /**
     * Register default listeners
     */
    private function registerDefaultListeners(): void
    {
        $this->eventDispatcher->addSubscriber(new PHPFileListener(new NamespaceResolver()));
        $this->eventDispatcher->addSubscriber(new ComposerJsonListener(new ComposerResolver()));
        $this->eventDispatcher->addSubscriber(new GitDirectoryListener());
        $this->eventDispatcher->addSubscriber(new RootDirectoryListener());
    }

    /**
     * Run application.
     */
    public function resolveNamespace(string $path): ?string
    {
        $namespace       = null;
        $eventDispatcher = $this->eventDispatcher;

        // loop as long a break listener doesn't stop propagation or we have empty directories
        // we iterate through directories up
        $loopedPaths = [];
        do {
            $directory = $this->getDirectoryContents($path);
            // loop over files/directories and check if a listener can find a namespace
            foreach ($directory as $item) {
                $namespaceEvent = new FileEvent($item);
                $namespaceEvent->setLoopedDirectories($loopedPaths);
                $eventDispatcher->dispatch($namespaceEvent, 'file.loop');

                // if a listener has found a namespace and because
                // of its priority is want to cancel we break early
                if ($namespaceEvent->isPropagationStopped()) {
                    $namespace = $namespaceEvent->getNamespace();
                    break 2;
                }

                // save a possible found namespace for the next iteration
                if ($namespaceEvent->getNamespace()) {
                    $namespace = $namespaceEvent->getNamespace();
                }
            }

            // we have found a namespace, so break early
            if ($namespace) {
                break;
            }

            // check for listeners that check if the path iteration loop should be stopped
            foreach ($directory as $item) {
                $breakEvent = new FileEvent($item);
                $eventDispatcher->dispatch($breakEvent, 'file.break');
                if (false !== $breakEvent->isPropagationStopped()) {
                    break 2;
                }
            }
            $loopedPaths[] = basename($path);
            $path          = dirname($path) !== 'vfs:' ? dirname($path) : 'vfs://';
            //break early cuz DirectoryIterator can't handle vfs root folder
        } while ((0 === count($directory) || ! $breakEvent->isPropagationStopped()) && $path !== 'vfs://');

        return $namespace;
    }

    /**
     * Get directory contents without dot files.
     *
     * @psalm-return array<int, DirectoryIterator>
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

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
}
