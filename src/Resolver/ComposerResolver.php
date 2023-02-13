<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Resolver;

use function array_diff;
use function array_reverse;
use function count;
use function current;
use function explode;
use function file_get_contents;
use function implode;
use function json_decode;
use function key;
use function ltrim;
use function preg_replace;
use function rtrim;

class ComposerResolver implements ResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolve(string $path, array $loopedDirectories): string|false
    {
        $namespace = false;
        $jsonFile  = file_get_contents($path);
        $json      = json_decode($jsonFile, true);

        if (null !== $json && isset($json['autoload'])) {
            $autoload = $json['autoload'];
            if (isset($autoload['psr-4'])) {
                $namespaces = $autoload['psr-4'];
                $namespace  = key($namespaces);
                $path       = current($namespaces);
            } elseif (isset($autoload['psr-0'])) {
                $namespaces = $autoload['psr-0'];
                $namespace  = key($namespaces);
                $path       = current($namespaces);
            }

            if (false !== $namespace) {
                $namespace = rtrim(preg_replace('/\s+/', '', $namespace), '\\');

                $namespaceDiff = array_reverse(array_diff($loopedDirectories, explode('/', $path)));
                $namespaceDiff = array_diff($namespaceDiff, explode('\\', $namespace));

                if (count($namespaceDiff) > 0) {
                    $namespace .= '\\' . implode('\\', $namespaceDiff);
                }
                $namespace = ltrim($namespace, '\\');
            }
        }

        return $namespace;
    }
}
