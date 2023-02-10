<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Exception;

use RuntimeException as BaseRuntimeException;

class RuntimeException extends BaseRuntimeException implements ExceptionInterface
{
}
