<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest\Initializer;

use Fabiang\ExceptionGenerator\IntegrationTest\Initializer\Options;

interface OptionsAwareInterface
{
    /**
     * Set options object.
     */
    public function setOptions(Options $options): void;

    /**
     * Get options object.
     */
    public function getOptions(): Options;
}
