<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Fabiang\ExceptionGenerator\IntegrationTest\Initializer\Options;
use Fabiang\ExceptionGenerator\IntegrationTest\Initializer\OptionsAwareInterface;

class ApplicationInitializer implements ContextInitializer
{
    protected Options $options;

    /**
     * @param Options $options Options to be passed to contexts.
     */
    public function __construct(array $options)
    {
        $this->options = new Options($options);
    }

    /**
     * Set clones options to object.
     */
    public function initializeContext(Context $context): void
    {
        if ($context instanceof OptionsAwareInterface) {
            $context->setOptions($this->options);
        }
    }
}
