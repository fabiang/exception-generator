<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;

class GitDirectoryListener extends AbstractBreakListener implements BreakListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public function onBreak(FileEvent $event): void
    {
        if ($event->getBasename() === '.git' && $event->isDir()) {
            $event->stopPropagation();
        }
    }
}
