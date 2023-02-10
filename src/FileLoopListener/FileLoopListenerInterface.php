<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface FileLoopListenerInterface extends EventSubscriberInterface
{
    /**
     * File was found.
     *
     * Resolver listener must implement this interface.
     */
    public function onFile(FileEvent $event): void;
}
