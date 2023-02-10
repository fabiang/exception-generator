<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Fabiang\ExceptionGenerator\Generator\ExceptionClassNames
 */
final class ExceptionClassNamesTest extends TestCase
{
    private ExceptionClassNames $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new ExceptionClassNames();
    }

    /**
     * @covers ::getExceptionClassNames
     */
    public function testGetExceptionClassNames(): void
    {
        $this->assertSame(
            [
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
            ],
            $this->object->getExceptionClassNames()
        );
    }
}
