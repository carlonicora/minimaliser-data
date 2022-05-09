<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Factories\MinimalismFactories;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data;
use CarloNicora\Minimalism\MinimaliserData\Factories\FileFactory;
use CarloNicora\Minimalism\MinimaliserData\Objects\DatabaseObject;
use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;
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
    /** @var Data  */
    private Data $minimaliser;

    /** @var SqlInterface  */
    private SqlInterface $data;

    /**
     * @param MinimalismFactories $minimalismFactories
     * @param string|null $function
     */
    public function __construct(
        MinimalismFactories $minimalismFactories,
        ?string $function = null,
    )
    {
        parent::__construct(
            minimalismFactories: $minimalismFactories,
            function: $function,
        );

        $this->minimaliser = $minimalismFactories->getServiceFactory()->create(Data::class);
        $this->data = $minimalismFactories->getServiceFactory()->create(SqlInterface::class);
    }

    /**
     * @param string $prompt
     * @param bool $isYesNoAnswer
     * @param string|bool|null $defaultAnswer
     * @return string|bool
     */
    private function readInput(
        string $prompt,
        bool $isYesNoAnswer=false,
        string|bool|null $defaultAnswer=null,
    ): string|bool
    {
        if ($defaultAnswer !== null){
            if ($isYesNoAnswer && $defaultAnswer === true) {
                $prompt .= '(Y/n)';
            } elseif ($isYesNoAnswer && $defaultAnswer === false){
                $prompt .= '(y/N)';
            } else {
                $prompt .= '(' . $defaultAnswer . ')';
            }
        }
        $prompt .= ': ';
        $textResponse = readline($prompt);

        if ($isYesNoAnswer){
            if ($textResponse === ''){
                $response = $defaultAnswer;
            } else {
                switch (strtolower($textResponse)) {
                    case 'y':
                        $response = true;
                        break;
                    case 'n':
                        $response = false;
                        break;
                    default:
                        echo 'The answer can be either Yes (y/Y) or No (N/n). Please reply with a valid answer.' . PHP_EOL;
                        $response = $this->readInput($prompt, $isYesNoAnswer, $defaultAnswer);
                        break;
                }
            }
        } else {
            if ($defaultAnswer !== null && $textResponse === ''){
                $response = $defaultAnswer;
            } else {
                $response = $textResponse;
            }
        }

        return $response;
    }

    /**
     * @return HttpCode
     * @throws Exception
     */
    public function cli(
    ): HttpCode
    {
        system('clear');
        $projectName = $this->readInput(prompt: 'Project Name');

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
                    $input = $this->readInput(prompt: 'Select database to import');
                    if (is_numeric($input) || $input <= count($dbs) - 1){
                        $databaseIdentifier = $dbs[$input];
                    } else {
                        echo 'Invalid selection';
                    }
                }
            }

            $database = new DatabaseObject(
                data: $this->data,
                projectName: $projectName,
                namespace: $this->minimaliser->getNamespace(),
                identifier: $databaseIdentifier,
            );

            $tables = [];

            foreach ($database->getTables() as $table) {
                $table = $this->initialiseObjectDetails($table);
                if ($table !== null){
                    $tables[] = $table;
                }
            }

            FileFactory::createFiles(
                projectName: $projectName,
                tables: $tables,
            );

            foreach ($tables as $table) {
                FileFactory::generateObjectFiles(
                    table: $table,
                );
            }
        } else {
            echo 'No databases specified in the .env file.';
        }

        exit;
    }

    /**
     * @param TableObject $table
     * @return TableObject|null
     */
    private function initialiseObjectDetails(
        TableObject $table,
    ): ?TableObject
    {
        system('clear');

        $fontBoldUnderlined = "\e[1;4m";
        $fontClose = "\e[0m";

        echo 'Generating object for table ' . $fontBoldUnderlined . $table->getName() . $fontClose . PHP_EOL;
        $objectNameSingular = $this->readInput(prompt: 'Singular name of the object', defaultAnswer: $table->getObjectName());
        $objectNamePlural = $this->readInput(prompt: 'Plural name of the object', defaultAnswer: $table->getObjectNamePlural());

        if ($objectNameSingular !== ''){
            $table->setObjectName($objectNameSingular);
        }

        if ($objectNamePlural !== ''){
            $table->setObjectNamePlural($objectNamePlural);
        }

        $write = true;
        if (file_exists($this->minimaliser->getDataDirectory() . $table->getObjectNamePlural())){
            if ($this->readInput(prompt: 'The object ' . $fontBoldUnderlined . $table->getObjectNamePlural() . $fontClose . ' already exists. Overrite?', isYesNoAnswer: true, defaultAnswer: false) === true){
                exec(sprintf("rm -rf %s", escapeshellarg($this->minimaliser->getDataDirectory() . $table->getObjectNamePlural())));
            } else {
                $write = false;
            }
        }

        if ($write === false) {
            return null;
        }

        if (!$this->readInput(prompt: 'Is this a primary table?', isYesNoAnswer: true, defaultAnswer: true)){
            $table->setIsNotComplete();
        }

        return $table;
    }
}