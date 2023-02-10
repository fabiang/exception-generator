<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest\Initializer;

use function array_key_exists;

class Options
{
    /** @var array */
    protected $options = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get option.
     */
    public function get(string $option, mixed $default = null): mixed
    {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        return $default;
    }

    public function set(string $option, mixed $value): void
    {
        $this->options[$option] = $value;
    }

    /**
     * Add a value to existing option
     */
    public function add(string $option, string $key, mixed $value): void
    {
        if (array_key_exists($option, $this->options)) {
            // @todo add behaviour when option is no array
            $this->options[$option][$key] = $value;
        }
    }

    /**
     * Delete option.
     */
    public function delete(string $option): void
    {
        unset($this->options[$option]);
    }
}
