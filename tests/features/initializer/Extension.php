<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest\Initializer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as BehatExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class Extension implements BehatExtension
{
    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder->addDefaultsIfNotSet()
            ->children()
            ->variableNode('options')->end()
            ->end();
    }

    public function getConfigKey(): string
    {
        return 'application_initializer';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(__NAMESPACE__ . '\ApplicationInitializer', [
            $config['options'],
        ]);

        $definition->addTag(ContextExtension::INITIALIZER_TAG);
        $container->setDefinition('application_initializer', $definition);
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
