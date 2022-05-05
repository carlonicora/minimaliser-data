<?php
namespace CarloNicora\Minimalism\MinimaliserData\Factories;

use CarloNicora\JsonApi\Document;
use CarloNicora\Minimalism\MinimaliserData\Enums\Generator;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Twig\Twig;
use Exception;

class FileFactory
{
    /**
     * @param Generator $type
     * @param Twig $twig
     * @param string $dataDirectory
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    public static function createObjectFile(
        Generator $type,
        Twig $twig,
        string $dataDirectory,
        TableObject $table,
    ): void
    {
        $folderName = $dataDirectory . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . $type->name;
        if (!file_exists($folderName)){
            mkdir($folderName);
        }

        $document = new Document();
        $document->addResource($table->generateResourceObject());

        $file = $twig->transform(
            document: $document,
            viewFile: $type->name,
        );

        file_put_contents($folderName . DIRECTORY_SEPARATOR . $type->getFileName($table), $file);
    }

    /*
    public static function createFile(
        SharedFile $type,
        Twig $twig,
        string $sourceDirectory,
        array $tables,
    ): void
    {

    }
    */
}