<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\Generator;

use DateTime;
use Exception;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplateMapResolver;

use function defined;
use function getenv;
use function rtrim;
use function str_replace;

class TemplateRenderer
{
    public function __construct(protected ?PhpRenderer $renderer = null)
    {
        $this->renderer = $renderer;
        if (null === $renderer) {
            $this->renderer = new PhpRenderer();
        }

        $this->renderer->setResolver(new TemplateMapResolver());
    }

    /**
     * Add a path to template resolver.
     *
     * @param string $type     Template type
     * @param string $template Template path
     */
    public function addPath(string $type, string $template): void
    {
        /** @var TemplateMapResolver $resolver */
        $resolver = $this->renderer->resolver();
        $resolver->add($type, $template);
    }

    /**
     * Render an exception template
     *
     * @param string $namespace     Namespace of class
     * @param string $use Path for BaseExceptions to use, if they exists
     * @param string $exceptionName Type of exception (if null a interface is rendered)
     */
    public function render(string $namespace, ?string $use = null, ?string $exceptionName = null): string
    {
        $model = new ViewModel();
        //replace because it will be added in template anyway
        $namespace = str_replace(Exception::class, '', $namespace);
        if (null !== $exceptionName) {
            $model->setTemplate('exception');
            $model->setVariable('exceptionName', $exceptionName);
            $model->setVariable('use', empty($use) ? $exceptionName : $use);
        } else {
            $model->setTemplate('interface');
            $model->setVariable('use', $use);
        }

        $model->setVariable('namespace', rtrim($namespace, '\\'));
        $model->setVariable('created', new DateTime());
        $model->setVariable('user', $this->getUsername());
        return $this->renderer->render($model);
    }

    private function getUsername(): string
    {
        if (defined('USER')) {
            return USER;
        }

        $user = getenv('USER');
        if (! empty($user)) {
            return $user;
        }

        $user = getenv('USERNAME');
        if (! empty($user)) {
            return $user;
        }

        return null;
    }

    public function getRenderer(): PhpRenderer
    {
        return $this->renderer;
    }
}
