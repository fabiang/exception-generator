<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest;

use Fabiang\ExceptionGenerator\IntegrationTest\Initializer\Options;
use Fabiang\ExceptionGenerator\IntegrationTest\Initializer\OptionsAwareInterface;

use function define;
use function defined;
use function ini_set;

/**
 * Abstract class for context classes.
 *
 * Getters and setters that are shared between all context classes.
 */
abstract class AbstractContext implements OptionsAwareInterface
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        if (! defined('USER')) {
            define('USER', 'behat');
        }

        ini_set('date.timezone', 'UTC');
    }

    private Options $options;

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }
}
