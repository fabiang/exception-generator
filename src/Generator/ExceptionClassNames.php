<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

class ExceptionClassNames
{
    public static function getExceptionClassNames(): array
    {
        return [
            'BadMethodCallException',
            'DomainException',
            'InvalidArgumentException',
            'LengthException',
            'LogicException',
            'OutOfBoundsException',
            'OutOfRangeException',
            'OverflowException',
            'RangeException',
            'RuntimeException',
            'UnderflowException',
            'UnexpectedValueException',
        ];
    }
}
