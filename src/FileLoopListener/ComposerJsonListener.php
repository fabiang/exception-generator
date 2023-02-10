<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\Resolver\ComposerResolver;

class ComposerJsonListener extends AbstractFileLoopListener implements FileLoopListenerInterface
{
    public function __construct(protected ComposerResolver $composerResolver)
    {
        $this->composerResolver = $composerResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function onFile(FileEvent $event): void
    {
        if ($event->getBasename() === 'composer.json') {
            $namespace = $this->composerResolver->resolve($event->getFile(), $event->getLoopedDirectories());

            if (false !== $namespace) {
                $event->setNamespace($namespace);
            }
        }
    }
}
