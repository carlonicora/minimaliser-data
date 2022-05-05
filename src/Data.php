<?php
namespace CarloNicora\Minimalism\MinimaliserData;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Services\Path;
use JsonException;

class Data extends AbstractService
{
    /** @var string  */
    private string $namespace;

    /** @var string  */
    private string $sourceFolder;

    /**
     * @param Path $path
     */
    public function __construct(
        private readonly Path $path,
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

    /**
     * @return void
     * @throws JsonException
     */
    private function initialiseComposer(
    ): void
    {
        if (!file_exists($this->path->getRoot() . DIRECTORY_SEPARATOR . 'composer.json')){
            echo 'Cannot find composer.json';
            exit;
        }

        $composerJson = file_get_contents(
            $this->path->getRoot() . DIRECTORY_SEPARATOR . 'composer.json'
        );

        $composer = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);
        $this->namespace = array_key_first($composer['autoload']['psr-4']);
        $this->sourceFolder = $this->path->getRoot() .  DIRECTORY_SEPARATOR .  $composer['autoload']['psr-4'][$this->namespace];

        if (!file_exists($this->sourceFolder)){
            echo 'Cannot find source folder';
            exit;
        }
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
}