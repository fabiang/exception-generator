<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Resolver;

interface ResolverInterface
{
    /**
     * Resolve namespace from file.
     */
    public function resolve(string $path, array $loopedDirectories): string|false;
}
