<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use org\bovigo\vfs\vfsStream;

use function file_get_contents;
use function file_put_contents;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function unlink;

/**
 * Defines application features from the specific context.
 */
class TemplateContext extends AbstractContext implements Context, SnippetAcceptingContext
{
    /**
     * @Given Directory structure for templates
     */
    public function directoryStructureForTemplates(): void
    {
        vfsStream::setup('root', null, [
            'project'           => [
                'templates' => [
                    'exception.phtml' => 'ExceptionTemplateCurrentDir',
                    'interface.phtml' => 'InterfaceTemplateCurrentDir',
                ],
            ],
            'global_templates'  => [
                'exception.phtml' => 'ExceptionTemplateGlobal',
                'interface.phtml' => 'InterfaceTemplateGlobal',
            ],
            'project_templates' => [
                'exception.phtml' => 'ExceptionTemplateProject',
                'interface.phtml' => 'InterfaceTemplateProject',
            ],
        ]);

        $this->getOptions()->set('path', vfsStream::url('root'));
        $this->getOptions()->set('home', $this->getOptions()->get('path'));
    }

    /**
     * @Given Template path is passed as option
     */
    public function templatePathIsPassedAsOption(): void
    {
        $this->getOptions()->add(
            'inputOptions',
            '--template-path',
            $this->getOptions()->get('path') . '/project/templates'
        );
    }

    /**
     * @Given Template path is not passed as option
     */
    public function templatePathIsNotPassedAsOption(): void
    {
        $options      = $this->getOptions();
        $inputOptions = $options->get('inputOptions');
        unset($inputOptions['--template-path']);
        $options->set('inputOptions', $inputOptions);
    }

    /**
     * @Given interface template is remove from passed template path
     */
    public function interfaceTemplateIsRemoveFromPassedTemplatePath(): void
    {
        unlink($this->getOptions()->get('path') . '/project/templates/interface.phtml');
    }

    /**
     * @Given Project template path configured in config
     */
    public function projectTemplatePathConfiguredInConfig(): void
    {
        $path = $this->getOptions()->get('path');
        file_put_contents(
            $path . '/.exception-generator.json',
            '{"templatepath":{"projects":{"' . $path . '/project":"'
                . $path . '/project_templates"}}}'
        );
    }

    /**
     * @Given Global template path configured in config
     */
    public function globalTemplatePathConfiguredInConfig(): void
    {
        $path = $this->getOptions()->get('path');
        file_put_contents(
            $path . '/.exception-generator.json',
            '{"templatepath":{"global": "' . $path . '/global_templates"}}'
        );
    }

    /**
     * @Then templates from template path should have been used
     */
    public function templatesFromTemplatePathShouldHaveBeenUsed(): void
    {
        $path          = $this->getOptions()->get('path');
        $exceptionFile = $path . '/project/Exception/BadMethodCallException.php';
        assertFileExists($exceptionFile);
        assertSame('ExceptionTemplateCurrentDir', file_get_contents($exceptionFile));
    }

    /**
     * @Then template from project configuration from global configuration should have been used
     */
    public function templateFromProjectConfigurationFromGlobalConfigurationShouldHaveBeenUsed(): void
    {
        $path          = $this->getOptions()->get('path');
        $exceptionFile = $path . '/project/Exception/BadMethodCallException.php';
        assertFileExists($exceptionFile);
        assertSame('ExceptionTemplateProject', file_get_contents($exceptionFile));
    }

    /**
     * @Then template from global configuration from global configuration should have been used
     */
    public function templateFromGlobalConfigurationFromGlobalConfigurationShouldHaveBeenUsed(): void
    {
        $path          = $this->getOptions()->get('path');
        $exceptionFile = $path . '/project/Exception/BadMethodCallException.php';
        assertFileExists($exceptionFile);
        assertSame('ExceptionTemplateGlobal', file_get_contents($exceptionFile));
    }

    /**
     * @Then template from passed path for interface shouldn't have been used
     */
    public function templateFromPassedPathForInterfaceShouldnTHaveBeenUsed(): void
    {
        $path          = $this->getOptions()->get('path');
        $exceptionFile = $path . '/project/Exception/ExceptionInterface.php';
        assertFileExists($exceptionFile);
        assertNotSame('InterfaceTemplateCurrentDir', file_get_contents($exceptionFile));
    }
}
