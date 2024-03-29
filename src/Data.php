<?php
namespace CarloNicora\Minimalism\MinimaliserData;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\MinimaliserData\Factories\FileFactory;
use CarloNicora\Minimalism\MinimaliserData\Factories\TestsFactory;
use CarloNicora\Minimalism\Services\Path;
use CarloNicora\Minimalism\Services\Twig\Twig;
use JsonException;
use RuntimeException;

class Data extends AbstractService
{
    /** @var string  */
    private string $namespace;

    /** @var string  */
    private string $sourceFolder;

    /** @var string  */
    private string $dataDirectory;

    /**
     * @param Path $path
     * @param Twig $twig
     */
    public function __construct(
        private readonly Path $path,
        private readonly Twig $twig,
        private readonly ?string $MINIMALISM_SERVICE_MICROSERVICE_DATA_ADDITIONAL_ENDPOINTS=null,
    )
    {
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function initialise(
    ): void
    {
        parent::initialise();

        $this->initialiseComposer();
    }

    public function getAdditionalEndpoints(): ?string {
        return $this->MINIMALISM_SERVICE_MICROSERVICE_DATA_ADDITIONAL_ENDPOINTS;
    }

    /**
     * @return void
     * @throws JsonException
     */
    private function initialiseComposer(
    ): void
    {
        if (!is_file($this->path->getRoot() . DIRECTORY_SEPARATOR . 'composer.json')){
            echo 'Cannot find composer.json';
            exit;
        }

        $composerJson = file_get_contents(
            $this->path->getRoot() . DIRECTORY_SEPARATOR . 'composer.json'
        );

        $composer = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);
        $this->namespace = array_key_first($composer['autoload']['psr-4']);
        $this->sourceFolder = $this->path->getRoot() .  DIRECTORY_SEPARATOR .  $composer['autoload']['psr-4'][$this->namespace];
        $testFolder = $this->path->getRoot() .  DIRECTORY_SEPARATOR .  $composer['autoload-dev']['psr-4'][$this->namespace . 'Tests\\'];
        $this->dataDirectory = $this->sourceFolder . 'Data' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->dataDirectory) && !mkdir($concurrentDirectory = $this->dataDirectory) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        if (!file_exists($this->sourceFolder)){
            echo 'Cannot find source folder';
            exit;
        }

        FileFactory::initialise(
            twig: $this->twig,
            dataDirectory: $this->dataDirectory,
            sourceDirectory: $this->sourceFolder,
        );

        TestsFactory::initialise(
            twig: $this->twig,
            testsDirectory: $testFolder,
        );
    }

    /**
     * @return string
     */
    public function getNamespace(
    ): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getSourceFolder(
    ): string
    {
        return $this->sourceFolder;
    }

    /**
     * @return string
     */
    public function getDataDirectory(
    ): string
    {
        return $this->dataDirectory;
    }
}