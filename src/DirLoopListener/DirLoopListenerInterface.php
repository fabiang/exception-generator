<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\DirLoopListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface DirLoopListenerInterface extends EventSubscriberInterface
{
    /**
     * File was found.
     *
     * Resolver listener must implement this interface.
     */
    public function onDir(FileEvent $event): void;
}
