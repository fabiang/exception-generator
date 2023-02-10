<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\TemplateResolver;

use function file_exists;
use function realpath;
use function rtrim;

class TemplateResolver
{
    /**
     * Path to template.
     */
    protected ?string $templatePath;

    /**
     * TemplatePathMatcher instance.
     */
    protected TemplatePathMatcher $templatePathMatcher;

    /**
     * transforms received path to a valid realpath
     */
    public function __construct(?string $templatePath, TemplatePathMatcher $templatePathMatcher)
    {
        $this->templatePath        = rtrim($templatePath ?? '', '/');
        $this->templatePathMatcher = $templatePathMatcher;
    }

    /**
     * resolves path for specific template
     */
    public function resolve(string $templateName): string
    {
        return $this->getTemplatePath($templateName) . '/' . $templateName;
    }

    /**
     * trys different paths to resolve a valid template path
     */
    protected function getTemplatePath(string $templateName): string
    {
        if (file_exists($this->templatePath . '/' . $templateName)) {
            return $this->templatePath;
        }

        $match = $this->templatePathMatcher->match($templateName);

        if (false === $match) {
            return realpath(__DIR__ . '/../../templates');
        }

        return $match;
    }
}
