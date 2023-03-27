<?php
namespace CarloNicora\Minimalism\MinimaliserData\Factories;

use CarloNicora\JsonApi\Document;
use CarloNicora\Minimalism\MinimaliserData\Enums\Generator;
use CarloNicora\Minimalism\MinimaliserData\Enums\SharedFile;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Discovery\Data\EndpointData;
use CarloNicora\Minimalism\Services\Discovery\Data\MethodData;
use CarloNicora\Minimalism\Services\Discovery\Data\MicroserviceData;
use CarloNicora\Minimalism\Services\Discovery\Data\ServiceData;
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

    /** @var ServiceData  */
    private static ServiceData $service;

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
        self::$service = new ServiceData($_ENV['MINIMALISM_SERVICE_DISCOVERY_SERVICE']);

        $microservice = new MicroserviceData($_ENV['MINIMALISM_SERVICE_DISCOVERY_SERVICE'] . '-' . $_ENV['MINIMALISM_SERVICE_DISCOVERY_MICROSERVICE']);
        $microservice->setName($_ENV['MINIMALISM_SERVICE_DISCOVERY_MICROSERVICE']);
        $microservice->setPublicKey($_ENV['MINIMALISM_SERVICE_DISCOVERY_PUBLIC_KEY']);
        $microservice->setDocker($_ENV['MINIMALISM_SERVICE_DISCOVERY_DOCKER']);
        $microservice->setUrl($_ENV['MINIMALISM_SERVICE_DISCOVERY_URL']);
        $microservice->setVersion($_ENV['MINIMALISM_SERVICE_DISCOVERY_VERSION']);
        $microservice->setHostname($_ENV['MINIMALISM_SERVICE_DISCOVERY_HOSTNAME']);

        self::$service->add($microservice);
    }

    /**
     * @return ServiceData
     */
    public static function getServiceData(): ServiceData {
        return self::$service;
    }

    /**
     * @param array $tables
     * @param string $name
     * @return TableObject|null
     */
    private static function tableExists(
        array $tables,
        string $name,
    ): TableObject|null {
        foreach ($tables as $table) {
            if (strtolower($table->getName()) === strtolower($name)){
                return $table;
            }
        }

        return null;
    }

    /**
     * @param TableObject[] $tables
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    public static function generateObjectFiles(
        array $tables,
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

        if ($table->isComplete()) {
            if ($table->isManyToMany()) {
                $foreignKeys = [];
                foreach ($table->getFields() as $field){
                    if ($field->isForeignKey()){
                        $foreignKeys[] = [
                            'table' => $field->getForeignKeyTable(),
                            'tableSingular' => Pluraliser::singular($field->getForeignKeyTable()),
                            'tableSingularCapitalised' => ucfirst(Pluraliser::singular($field->getForeignKeyTable())),
                            'tableCapitalised' => ucfirst($field->getForeignKeyTable()),
                            'field' => $field->getForeignKeyField(),
                            'fieldCapitalised' => ucfirst($field->getForeignKeyField()),
                            'isExistingTable' => self::tableExists($tables, $field->getForeignKeyTable()) !== null,
                        ];
                    }
                }

                if (count($foreignKeys) === 2){
                    self::createManyToManyModel($table, $foreignKeys[0], $foreignKeys[1]);
                    self::createManyToManyModel($table, $foreignKeys[1], $foreignKeys[0]);
                }
            }else {
                $endpoint = new EndpointData($_ENV['MINIMALISM_SERVICE_DISCOVERY_SERVICE'] . '-' . $_ENV['MINIMALISM_SERVICE_DISCOVERY_MICROSERVICE'] . '-' . $table->getName());
                $endpoint->setName($table->getName());
                $endpoint->add(new MethodData('post'));
                $endpoint->add(new MethodData('get'));
                $endpoint->add(new MethodData('delete'));
                $endpoint->add(new MethodData('patch'));
                self::$service->findChild($_ENV['MINIMALISM_SERVICE_DISCOVERY_SERVICE'] . '-' . $_ENV['MINIMALISM_SERVICE_DISCOVERY_MICROSERVICE'])?->add($endpoint);


                self::createObjectFile(type: Generator::UpdaterValidators, table: $table);
                self::createObjectFile(type: Generator::AbstractBuilders, table: $table);
                self::createObjectFile(type: Generator::Builders, table: $table);
                self::createObjectFile(type: Generator::CreatorValidators, table: $table);
                self::createObjectFile(type: Generator::Models, table: $table);
                self::createObjectFile(type: Generator::AbstractCaches, table: $table);
                self::createObjectFile(type: Generator::Caches, table: $table);

                foreach ($table->getFields() ?? [] as $field){
                    if ($field->isForeignKey()){
                        $document = new Document();
                        $resource = $table->generateResourceObject();
                        $resource->meta->add('parent', [
                            'tableSingular' => Pluraliser::singular($field->getForeignKeyTable()),
                            'tablePlural' => Pluraliser::plural($field->getForeignKeyTable()),
                            'tableSingularCapitalised' => ucfirst(Pluraliser::singular($field->getForeignKeyTable())),
                            'tablePluralCapitalised' => ucfirst(Pluraliser::plural($field->getForeignKeyTable())),
                            'field' => $field->getForeignKeyField(),
                            'fieldCapitalised' => ucfirst($field->getForeignKeyField()),
                        ]);
                        $document->addResource($resource);

                        $directory = self::$sourceDirectory . 'Models' . DIRECTORY_SEPARATOR . ucfirst(Pluraliser::plural($field->getForeignKeyTable()));
                        $fileName = $directory . DIRECTORY_SEPARATOR . ucfirst($table->getObjectNamePlural());
                        $fileName .= '.php';

                        if (!is_dir($directory)){
                            mkdir($directory);
                        }

                        $file = self::$twig->transform(
                            document: $document,
                            viewFile: 'ChildModelExistingTable',
                        );
                        file_put_contents($fileName, $file);
                    }
                }
            }
        }
    }

    /**
     * @param TableObject $table
     * @param array $source
     * @param array $destination
     * @return void
     * @throws Exception
     */
    private static function createManyToManyModel(
        TableObject $table,
        array $source,
        array $destination,
    ): void {
        $directory = self::$sourceDirectory . 'Models' . DIRECTORY_SEPARATOR . ucfirst($source['table']);
        $fileName = $directory . DIRECTORY_SEPARATOR . ucfirst($destination['table']);
        $fileName .= '.php';

        if (!is_dir($directory)){
            mkdir($directory);
        }

        $document = new Document();
        $resource = $table->generateResourceObject();
        $resource->meta->add('source', $source);
        $resource->meta->add('destination', $destination);
        $document->addResource($resource);

        $file = self::$twig->transform(
            document: $document,
            viewFile: 'ManyToManyModels',
        );
        file_put_contents($fileName, $file);
    }

    /**
     * @param Generator $type
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    private static function createObjectFile(
        Generator $type,
        TableObject $table,
    ): void
    {
        $folderName = self::$sourceDirectory . $type->getDataSubfolder(table: $table);
        if (!is_dir($folderName)){
            mkdir(directory: $folderName, recursive: true);
        }

        $fileName = $folderName . $type->getFileName(table: $table);

        if (!$type->overrides() && file_exists($fileName)){
            return;
        }

        $document = new Document();

        $tableResource = $table->generateResourceObject();
        $document->addResource($tableResource);

        $file = self::$twig->transform(
            document: $document,
            viewFile: $type->name,
        );

        file_put_contents($folderName . $type->getFileName(table: $table), $file);
    }

    /**
     * @param string $projectName
     * @param string[] $loadedServices
     * @param TableObject[] $tables
     * @return void
     * @throws Exception
     */
    public static function createFiles(
        string $projectName,
        array $loadedServices,
        array $tables,
    ): void
    {
        $document = new Document();
        $document->meta->add(name: 'projectName', value: $projectName);
        $document->meta->add(name: 'imgix', value: $loadedServices['CarloNicora\Minimalism\Services\Imgix\Imgix'] !== null);

        foreach ($tables as $key => $table){
            $document->addResource($table->generateResourceObject());

            if ($key === array_key_first($tables)) {
                $document->meta->add(name: 'namespace', value: $table->getNamespace());
            }
        }

        $enumDirectory = self::$sourceDirectory . 'Enums';
        if (!is_dir($enumDirectory)){
            mkdir($enumDirectory);
        }

        $modelsDirectory = self::$sourceDirectory . 'Models';
        if (!is_dir($modelsDirectory)){
            mkdir($modelsDirectory);
        }

        $modelsDirectory .= DIRECTORY_SEPARATOR . 'Abstracts';
        if (!is_dir($modelsDirectory)){
            mkdir($modelsDirectory);
        }

        $abstractDirectory = self::$dataDirectory . 'Abstracts';
        if (!is_dir($abstractDirectory)){
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