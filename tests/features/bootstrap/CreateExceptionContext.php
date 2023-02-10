<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use InvalidArgumentException;

use function file_get_contents;
use function file_put_contents;
use function json_encode;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertStringMatchesFormat;

/**
 * Defines application features from the specific context.
 */
class CreateExceptionContext extends AbstractContext implements Context, SnippetAcceptingContext
{
    /**
     * @Given a path containing php classes with namespaces
     */
    public function aPathContainingPhpClassesWithNamespaces(): void
    {
        file_put_contents(
            $this->getOptions()->get('path') . '/project/src/Foo/MyClass.php',
            '<?php namespace Foo; class Test {}'
        );
    }

    /**
     * @Given a path containing php classes with namespaces in same path
     */
    public function aPathContainingPhpClassesWithNamespacesInSamePath(): void
    {
        file_put_contents(
            $this->getOptions()->get('path') . '/project/src/Foo/Bar/My/MyClass.php',
            '<?php namespace Foo\Bar\My; class Test {}'
        );
    }

    /**
     * @Given a path containing a composer.json with a :namespaceType namespace
     */
    public function aPathContainingAComposerJsonWithANamespace(string $namespaceType): void
    {
        $composerJson = ['autoload' => []];

        switch ($namespaceType) {
            case 'psr-4':
                $composerJson['autoload']['psr-4'] = [
                    'Foo\Bar\\' => 'src/Foo/Bar/',
                ];
                break;
            case 'psr-0':
                $composerJson['autoload']['psr-0'] = [
                    'Foo\Bar\\' => 'src/',
                ];
                break;
            default:
                throw new InvalidArgumentException('Invalid namespace type given');
        }
        file_put_contents(
            $this->getOptions()->get('path') . '/project/composer.json',
            json_encode($composerJson)
        );
    }

    /**
     * @Then a file named :file should be created in :path with content
     */
    public function aFileNamedShouldBeCreatedWithContent(string $file, string $path, PyStringNode $content): void
    {
        $file = $this->getOptions()->get('path') . $path . $file;
        assertFileExists($file);
        assertStringMatchesFormat($content->getRaw(), file_get_contents($file));
    }

    /**
     * @Given option for disabling parent exception search is :option
     */
    public function applicationWithDisabledParentSearch(string $option): void
    {
        if ('set' === $option) {
            $this->getOptions()->add('inputOptions', '--no-parents', true);
        } else {
            $this->getOptions()->add('inputOptions', '--no-parents', false);
        }
    }
}
