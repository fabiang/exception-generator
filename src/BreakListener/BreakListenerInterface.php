<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\BreakListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface BreakListenerInterface extends EventSubscriberInterface
{
    /**
     * Listener for file breaks.
     */
    public function onBreak(FileEvent $event): void;
}
