<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\FileLoopListener;

use Fabiang\ExceptionGenerator\Event\FileEvent;
use Fabiang\ExceptionGenerator\Resolver\NamespaceResolver;

class PHPFileListener extends AbstractFileLoopListener implements FileLoopListenerInterface
{
    public function __construct(protected NamespaceResolver $namespaceResolver)
    {
        $this->namespaceResolver = $namespaceResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function onFile(FileEvent $event): void
    {
        if ($event->getExtension() === 'php') {
            $namespace = $this->namespaceResolver->resolve($event->getFile(), $event->getLoopedDirectories());

            if (false !== $namespace) {
                $event->stopPropagation();
                $event->setNamespace($namespace);
            }
        }
    }
}
