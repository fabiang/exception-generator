<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;

use function preg_match;

use const DIRECTORY_SEPARATOR;

class RootDirectoryListener extends AbstractBreakListener implements BreakListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public function onBreak(FileEvent $event): void
    {
        $dirname = $event->getDirname();
        if (
            DIRECTORY_SEPARATOR === $dirname // on Unix systems we loop until we reach '/'
            || preg_match('#^[a-zA-Z]+:\\\\$#', $dirname) // on Windows we match against 'x:\\'
            || $dirname === 'vfs://'
        ) {
            $event->stopPropagation();
        }
    }
}
