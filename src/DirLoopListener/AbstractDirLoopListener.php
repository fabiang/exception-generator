<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\DirLoopListener;

abstract class AbstractDirLoopListener implements DirLoopListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'dir.loop' => ['onDir', 0],
        ];
    }
}
