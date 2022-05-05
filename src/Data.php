<?php
namespace CarloNicora\Minimalism\MinimaliserData;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use JsonException;

class Data extends AbstractService
{
    /** @var string  */
    private string $namespace;

    /** @var string  */
    private string $sourceFolder;

    /**
     * @return void
     * @throws JsonException
     */
    public function initialise(
    ): void
    {
        parent::initialise();

        $root = dirname(__DIR__, 4);

        if (!file_exists($root . DIRECTORY_SEPARATOR . 'composer.json')){
            echo 'Cannot find composer.json';
            exit;
        }

        $composerJson = file_get_contents(
            $root . DIRECTORY_SEPARATOR . 'composer.json'
        );

        $composer = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);
        $this->namespace = array_key_first($composer['autoload']['psr-4']);
        $this->sourceFolder = $root .  DIRECTORY_SEPARATOR .  $composer['autoload']['psr-4'][$this->namespace];

        $a = 1;
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