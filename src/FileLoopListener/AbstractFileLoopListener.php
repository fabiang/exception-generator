<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

abstract class AbstractFileLoopListener implements FileLoopListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'file.loop' => ['onFile', 0],
        ];
    }
}
