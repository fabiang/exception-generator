<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\TemplateResolver;

use Fabiang\ExceptionGenerator\Exception\RuntimeException;

use function array_values;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_readable;
use function json_decode;
use function json_last_error;
use function strlen;
use function strpos;
use function uksort;

use const JSON_ERROR_NONE;

class TemplatePathMatcher
{
    public const CONFIG_NAME = '.exception-generator.json';

    protected string $currentDir;
    protected string $configPath;

    /**
     * defines current dir and path for config
     */
    public function __construct(string $currentDir, string $configPath)
    {
        $this->currentDir = $currentDir;
        $this->configPath = $configPath . '/' . self::CONFIG_NAME;
    }

    /**
     * checks if config is valid and returns matching paths
     *
     * @throws RuntimeException
     */
    public function match(string $templateName): false|string
    {
        if (! is_readable($this->configPath)) {
            return false;
        }

        $jsonData = json_decode(file_get_contents($this->configPath), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($jsonData)) {
            throw new RuntimeException("Could not parse json configuration \"$this->configPath\".");
        }

        return $this->getPaths($jsonData, $templateName);
    }

    /**
     * trys to get the most matching path or global from config
     */
    protected function getPaths(array $configData, string $templateName): false|string
    {
        if (! isset($configData['templatepath']) || ! is_array($configData['templatepath'])) {
            return false;
        }

        $templatePath = $configData['templatepath'];

        if (isset($templatePath['projects']) && is_array($templatePath['projects'])) {
            $filteredProjects = $this->filterMatchingPaths($templatePath['projects']);

            $matchingPath = $this->getMostRelatedPath($filteredProjects, $templateName);
            if (false !== $matchingPath) {
                return $matchingPath;
            }
        }

        if (isset($templatePath['global'])) {
            $globalPath = $templatePath['global'];
            if (file_exists($globalPath . '/' . $templateName)) {
                return $globalPath;
            }
        }

        return false;
    }

    /**
     * filters paths matching to current directory
     */
    public function filterMatchingPaths(array $projects): array
    {
        $filteredProjects = [];
        foreach ($projects as $path => $projectTemplatePath) {
            // @todo Windows: case-insensitive match with stripos?
            if (false !== strpos($this->currentDir, $path)) {
                $filteredProjects[$path] = $projectTemplatePath;
            }
        }

        return $filteredProjects;
    }

    /**
     * trys to get the most related path where template was found
     */
    protected function getMostRelatedPath(array $filteredProjects, string $templateName): false|string
    {
        uksort($filteredProjects, function ($a, $b) {
            $strlenA = strlen($a);
            $strlenB = strlen($b);

            if ($strlenA < $strlenB) {
                return 1;
            }
            return -1;
        });

        $filteredProjects = array_values($filteredProjects);

        foreach ($filteredProjects as $templatePath) {
            if (file_exists($templatePath . '/' . $templateName)) {
                return $templatePath;
            }
        }
        return false;
    }
}
