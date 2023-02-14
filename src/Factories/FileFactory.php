<?php
namespace CarloNicora\Minimalism\MinimaliserData\Factories;

use CarloNicora\JsonApi\Document;
use CarloNicora\Minimalism\MinimaliserData\Enums\Generator;
use CarloNicora\Minimalism\MinimaliserData\Enums\SharedFile;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Twig\Twig;
use Exception;

class FileFactory
{
    /** @var Twig  */
    private static Twig $twig;

    /** @var string  */
    private static string $dataDirectory;

    /** @var string  */
    private static string $sourceDirectory;

    /**
     * @param Twig $twig
     * @param string $dataDirectory
     * @param string $sourceDirectory
     * @return void
     */
    public static function initialise(
        Twig $twig,
        string $dataDirectory,
        string $sourceDirectory,
    ): void
    {
        self::$twig = $twig;
        self::$dataDirectory = $dataDirectory;
        self::$sourceDirectory = $sourceDirectory;
    }

    /**
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    public static function generateObjectFiles(
        TableObject $table,
    ): void
    {
        if (!is_dir(self::$dataDirectory . $table->getObjectNamePlural())) {
            mkdir(self::$dataDirectory . $table->getObjectNamePlural());
        }

        self::createObjectFile(type: Generator::Databases,table: $table);
        self::createObjectFile(type: Generator::DataObjects, table: $table);
        self::createObjectFile(type: Generator::AbstractIO, table: $table);
        self::createObjectFile(type: Generator::IO, table: $table);

        if (!$table->isManyToMany() && $table->isComplete()) {
            self::createObjectFile(type: Generator::AbstractBuilders,table: $table);
            self::createObjectFile(type: Generator::Builders,table: $table);
            self::createObjectFile(type: Generator::UpdaterValidators, table: $table);
            self::createObjectFile(type: Generator::CreatorValidators, table: $table);
            self::createObjectFile(type: Generator::Models, table: $table);
            self::createObjectFile(type: Generator::AbstractCaches, table: $table);
            self::createObjectFile(type: Generator::Caches, table: $table);

            foreach ($table->getChildren() ?? [] as $foreignKey){
                self::createObjectFile(type: Generator::ChildModels, table: $table, childTable: $foreignKey->getTable());
            }
        }
    }

    /**
     * @param Generator $type
     * @param TableObject $table
     * @param TableObject|null $childTable
     * @return void
     * @throws Exception
     */
    private static function createObjectFile(
        Generator $type,
        TableObject $table,
        ?TableObject $childTable=null,
    ): void
    {
        $folderName = self::$sourceDirectory . $type->getDataSubfolder(table: $table);
        if (!file_exists($folderName)){
            mkdir(directory: $folderName, recursive: true);
        }

        if ($childTable === null) {
            $fileName = $folderName . $type->getFileName(table: $table);
        } else {
            $fileName = $folderName . $type->getFileName(table: $childTable);
        }

        if (!$type->overrides() && file_exists($fileName)){
            return;
        }

        $document = new Document();

        $tableResource = $table->generateResourceObject();
        if ($childTable !== null){
            $tableResource->meta->add(name: 'childTable', value: $childTable->getName());
        }
        $document->addResource($tableResource);

        $file = self::$twig->transform(
            document: $document,
            viewFile: $type->name,
        );

        if ($childTable === null) {
            file_put_contents($folderName . $type->getFileName(table: $table), $file);
        } else {
            file_put_contents($folderName . $type->getFileName(table: $childTable), $file);
        }
    }

    /**
     * @param string $projectName
     * @param TableObject[] $tables
     * @return void
     * @throws Exception
     */
    public static function createFiles(
        string $projectName,
        array $tables,
    ): void
    {
        $document = new Document();
        $document->meta->add(name: 'projectName', value: $projectName);
        
        foreach ($tables as $key => $table){
            $document->addResource($table->generateResourceObject());

            if ($key === array_key_first($tables)) {
                $document->meta->add(name: 'namespace', value: $table->getNamespace());
            }
        }

        $enumDirectory = self::$sourceDirectory . 'Enums';
        if (!file_exists($enumDirectory)){
            mkdir($enumDirectory);
        }

        $modelsDirectory = self::$sourceDirectory . 'Models';
        if (!file_exists($modelsDirectory)){
            mkdir($modelsDirectory);
        }

        $modelsDirectory .= DIRECTORY_SEPARATOR . 'Abstracts';
        if (!file_exists($modelsDirectory)){
            mkdir($modelsDirectory);
        }

        $abstractDirectory = self::$dataDirectory . 'Abstracts';
        if (!file_exists($abstractDirectory)){
            mkdir($abstractDirectory);
        }

        # DICTIONARY
        $file = self::$twig->transform(
            document: $document,
            viewFile: SharedFile::Dictionary->name,
        );
        file_put_contents($enumDirectory . DIRECTORY_SEPARATOR . SharedFile::Dictionary->getFileName($projectName), $file);

        # CREATE DATA/ABSTRACTS/DATA OBJECT
        $file = self::$twig->transform(
            document: $document,
            viewFile: SharedFile::AbstractDataObject->name,
        );
        file_put_contents($abstractDirectory . DIRECTORY_SEPARATOR . SharedFile::AbstractDataObject->getFileName($projectName), $file);

        # CREATE DATA/ABSTRACTS/RESOURCE BUILDER
        $file = self::$twig->transform(
            document: $document,
            viewFile: SharedFile::AbstractResourceBuilder->name,
        );
        file_put_contents($abstractDirectory . DIRECTORY_SEPARATOR . SharedFile::AbstractResourceBuilder->getFileName($projectName), $file);

        # CREATE DATA/ABSTRACTS/SQL IO
        $file = self::$twig->transform(
            document: $document,
            viewFile: SharedFile::AbstractSqlIO->name,
        );
        file_put_contents($abstractDirectory . DIRECTORY_SEPARATOR . SharedFile::AbstractSqlIO->getFileName($projectName), $file);

        # CREATE MODELS/ABSTRACTS/MODEL
        $file = self::$twig->transform(
            document: $document,
            viewFile: SharedFile::AbstractModel->name,
        );
        file_put_contents($modelsDirectory . DIRECTORY_SEPARATOR . SharedFile::AbstractModel->getFileName($projectName), $file);
    }
}