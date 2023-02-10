<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\DirLoopListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;

class ExceptionDirListener extends AbstractDirLoopListener implements DirLoopListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public function onDir(FileEvent $event): void
    {
        if ($event->getBasename() === 'Exception' && $event->isDir()) {
            $event->setParentExceptionDir();
            $event->stopPropagation();
        }
    }
}
