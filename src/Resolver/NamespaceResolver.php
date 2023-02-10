<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Resolver;

use Fabiang\ExceptionGenerator\Exception\RuntimeException;

use function array_reverse;
use function count;
use function file_get_contents;
use function implode;
use function is_array;
use function is_readable;
use function ltrim;
use function token_get_all;
use function trim;

use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_NS_SEPARATOR;
use const T_STRING;
use const T_WHITESPACE;

class NamespaceResolver implements ResolverInterface
{
    private const T_WHITESPACE           = T_WHITESPACE;
    private const T_NAMESPACE            = T_NAMESPACE;
    private const T_NS_SEPARATOR         = T_NS_SEPARATOR;
    private const T_STRING               = T_STRING;
    private const T_NAME_FULLY_QUALIFIED = T_NAME_FULLY_QUALIFIED;
    private const T_NAME_QUALIFIED       = T_NAME_QUALIFIED;

    public function resolve(string $path, array $loopedDirectories): string|bool
    {
        if (! is_readable($path)) {
            throw new RuntimeException('PHP file "' . $path . '" isn\'t readable');
        }

        $namespace = false;
        $tokens    = token_get_all(file_get_contents($path));

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $type = $token[0];
            } elseif ($namespace && $token === ';') {
                $namespace = ltrim(trim($namespace), '\\');

                // adding looped folders to namespace
                if (count($loopedDirectories) > 0) {
                    $namespace .= '\\' . implode('\\', array_reverse($loopedDirectories));
                }

                break;
            }

            if (self::T_NAMESPACE === $type) {
                $namespace = '';
                continue;
            }

            $lookForToken = false !== $namespace && $type !== self::T_WHITESPACE;
            $validToken   = $type === self::T_STRING ||
                $type === self::T_NS_SEPARATOR ||
                $type === self::T_NAME_FULLY_QUALIFIED || /* PHP8 */
                $type === self::T_NAME_QUALIFIED; /* PHP8 */

            if ($lookForToken && $validToken) {
                $namespace .= $token[1];
            } elseif ($lookForToken && ! $validToken) {
                $namespace = false;
                break;
            }
        }

        return $namespace;
    }
}
