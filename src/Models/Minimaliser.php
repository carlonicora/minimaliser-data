<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data;
use CarloNicora\Minimalism\MinimaliserData\Enums\Generator;
use CarloNicora\Minimalism\MinimaliserData\Factories\FileFactory;
use CarloNicora\Minimalism\MinimaliserData\Objects\DatabaseObject;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
use CarloNicora\Minimalism\Services\Twig\Twig;
use Exception;

/*
 * Ask for the name of the project (INPUT)
 * Read all the tables
 * Foreach Table (INPUT confirm endpoint + object name)
 * Create Dictionary
 * Abstract Data Object
 * Abstract Resource Builder
 * Foreach Table create Builders|Databases|DataObjects|Factories|IO|Validators
 */
class Minimaliser extends AbstractModel
{
    /** @var Twig  */
    private Twig $twig;

    /** @var string  */
    private string $dataDirectory;

    /**
     * @param Data $minimaliser
     * @param SqlInterface $data
     * @param Twig $twig
     * @return HttpCode
     * @throws Exception
     */
    public function cli(
        Data $minimaliser,
        SqlInterface $data,
        Twig $twig,
    ): HttpCode
    {
        $this->twig = $twig;

        system('clear');
        $projectName = readline('Project Name: ');

        $databaseIdentifier = null;

        if (array_key_exists('MINIMALISM_SERVICE_MYSQL', $_ENV) && $_ENV['MINIMALISM_SERVICE_MYSQL'] !== ''){
            $dbs = explode(',', $_ENV['MINIMALISM_SERVICE_MYSQL']);
            if (count($dbs) === 1){
                $databaseIdentifier = $dbs[0];
            } else {
                echo 'Available database:' . PHP_EOL;
                foreach ($dbs as $dbKey => $dbName){
                    echo '  ' . $dbKey . '. ' . $dbName . PHP_EOL;
                }

                while ($databaseIdentifier === null) {
                    $input = readline('Select database to import: ');
                    if (is_numeric($input) || $input <= count($dbs) - 1){
                        $databaseIdentifier = $dbs[$input];
                    } else {
                        readline('Invalid selection. Please hit return to select a correct database');
                    }
                }
            }

            $database = new DatabaseObject(
                data: $data,
                projectName: $projectName,
                namespace: $minimaliser->getNamespace(),
                identifier: $databaseIdentifier,
            );

            $this->dataDirectory = $minimaliser->getSourceFolder() . 'Data' . DIRECTORY_SEPARATOR;
            if (!file_exists($minimaliser->getSourceFolder() . 'Data')) {
                mkdir($minimaliser->getSourceFolder() . 'Data');
            }

            foreach ($database->getTables() as $table) {
                system('clear');

                $fontBoldUnderlined = "\e[1;4m";
                $fontClose = "\e[0m";

                echo 'Generating object for table ' . $fontBoldUnderlined . $table->getName() . $fontClose . PHP_EOL;
                $objectNameSingular = readline('Singular name of the object (' . $table->getObjectName() . '): ');
                $objectNamePlural = readline('Plural name of the object (' . $table->getObjectNamePlural() . '): ');
                $isComplete = readline('Is this a primary table? (Y/n)');

                if ($objectNameSingular !== ''){
                    $table->setObjectName($objectNameSingular);
                }

                if ($objectNamePlural !== ''){
                    $table->setObjectNamePlural($objectNamePlural);
                }

                if (strtolower($isComplete) === 'n'){
                    $table->setIsNotComplete();
                }
            }

            //FileFactory::createFile(type: Generator::Builders,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
            //generate Dictionary
            //generate AbstractDataObject
            //generate AbstractResourceBuilder

            foreach ($database->getTables() as $table) {
                $this->generateFiles($table);
            }
        } else {
            echo 'No databases specified in the .env file.';
        }

        exit;
    }

    /**
     * @param TableObject $table
     * @return void
     * @throws Exception
     */
    private function generateFiles(
        TableObject $table,
    ): void
    {
        if (file_exists($this->dataDirectory . $table->getObjectNamePlural())) {
            exec(sprintf("rm -rf %s", escapeshellarg($this->dataDirectory . $table->getObjectNamePlural())));
        }

        mkdir($this->dataDirectory . $table->getObjectNamePlural());

        FileFactory::createObjectFile(type: Generator::Databases,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
        
        if ($table->isComplete()) {
            FileFactory::createObjectFile(type: Generator::Builders,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
            FileFactory::createObjectFile(type: Generator::DataObjects, twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
            //FileFactory::createObjectFile(type: Generator::Factories,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
            //FileFactory::createObjectFile(type: Generator::IO,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
            //FileFactory::createObjectFile(type: Generator::Validators,twig: $this->twig, dataDirectory: $this->dataDirectory, table: $table);
        }
    }
}