<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

abstract class AbstractBreakListener implements BreakListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'file.break' => ['onBreak'],
        ];
    }
}
