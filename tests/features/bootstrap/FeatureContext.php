<?php

declare(strict_types=1);

namespace Fabiang\ExceptionGenerator\IntegrationTest;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Gherkin\Node\PyStringNode;
use Fabiang\ExceptionGenerator\Cli\Console\Application;
use Fabiang\ExceptionGenerator\Generator\ExceptionClassNames;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Input\ArrayInput;

use function array_merge;
use function array_rand;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertStringMatchesFormat;
use function PHPUnit\Framework\assertTrue;
use function range;
use function rtrim;
use function str_replace;
use function unlink;

/**
 * Given/When/Thens/Hooks shared between features
 */
class FeatureContext extends AbstractContext implements Context, SnippetAcceptingContext
{
    /** @AfterFeature */
    public static function teardownFeature(AfterFeatureScope $scope): void
    {
    }

    /**
     * @Given Directory structure with namespaces
     */
    public function directoryStructureWithNamespaces(): void
    {
        vfsStream::setup('root', null, [
            'project' => [
                'src' => [
                    'Foo' => [
                        'Bar' => [
                            'My' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $this->getOptions()->set('path', vfsStream::url('root'));
        $this->getOptions()->set('home', $this->getOptions()->get('path'));
    }

    /**
     * @Given Application with current path :path
     */
    public function applicationWithCurrentPath(string $path): void
    {
        $this->getOptions()->add('inputOptions', 'path', $this->getOptions()->get('path') . '/' . $path);
    }

    /**
     * @Given existing exception classes in path :path
     */
    public function existingExceptionClassesInPath(string $path): void
    {
        $path = vfsStream::url('root/' . $path) . '/Exception/';

        mkdir($path, 0777, true);
        $namespace     = rtrim(str_replace([vfsStream::url('root/project/src/'), '/'], ['', '\\'], $path), '\\');
        $interface     = "<?php\nnamespace $namespace;\n\ninterface ExceptionInterface\n{\n}\n";
        $interfacePath = $path . 'ExceptionInterface.php';
        file_put_contents($interfacePath, $interface);

        $classNames = ExceptionClassNames::getExceptionClassNames();
        foreach ($classNames as $className) {
            $classContent = "<?php\nnamespace $namespace;\n\n"
                . "use $className as Base$className;\n\n"
                . "class $className extends Base$className implements ExceptionInterface\n{\n}\n";
            $classPath    = $path . $className . '.php';
            file_put_contents($classPath, $classContent);
        }
    }

    /**
     * @Given dummy files and folders in all directories
     */
    public function dummyFilesInAllDirectories(): void
    {
        $inputOptions  = $this->getOptions()->get('inputOptions');
        $path          = $inputOptions['path'];
        $characterList = array_merge(range(0, 9), range('a', 'z'));
        $rndString     = $this->randomString(7);

        do {
            foreach ($characterList as $char) {
                file_put_contents(
                    $path . '/' . $char . '_DummyFile_' . $rndString . '.txt',
                    'if you read this you can read'
                );
                mkdir($path . '/' . $char . '_DummyDir_' . $rndString);
            }
            $path = dirname($path) !== 'vfs:' ? dirname($path) : 'vfs://';
        } while ($path !== 'vfs://');
    }

    /**
     * @Given File :filename is removed from :path
     */
    public function fileIsRemovedFrom(string $filename, string $path): void
    {
        $path = vfsStream::url('root/' . $path);
        assertTrue(unlink($path . $filename));
        assertFileDoesNotExist($path . $filename);
    }

    /**
     * @Then File :filename is restored in :path
     */
    public function fileIsRestoredIn(string $filename, string $path, PyStringNode $fileContents): void
    {
        $file = vfsStream::url('root/' . $path) . $filename;
        assertFileExists($file);
        assertStringMatchesFormat($fileContents->getRaw(), file_get_contents($file));
    }

    /**
     * @When the application is executed
     */
    public function theApplicationIsExecuted(): void
    {
        $inputOptions = $this->getOptions()->get('inputOptions');
        $input        = new ArrayInput($inputOptions);
        $application  = new Application();
        $application->setHome($this->getOptions()->get('home'));
        $application->setAutoExit(false);
        $application->run($input);
    }

    private function randomString(int $length): string
    {
        $key  = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $key;
    }
}
