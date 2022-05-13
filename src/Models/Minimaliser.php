<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Factories\MinimalismFactories;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data;
use CarloNicora\Minimalism\MinimaliserData\Factories\FileFactory;
use CarloNicora\Minimalism\MinimaliserData\Factories\TestsFactory;
use CarloNicora\Minimalism\MinimaliserData\Objects\DatabaseObject;
use Exception;

class Minimaliser extends AbstractModel
{
    /** @var Data  */
    private Data $minimaliser;

    /** @var SqlInterface  */
    private SqlInterface $data;

    /** @var DatabaseObject  */
    private DatabaseObject $database;

    /** @var string|null  */
    private ?string $databaseIdentifier=null;

    /** @var string  */
    private string $projectName;

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
        } else if ($defaultAnswer !== null && $textResponse === ''){
            $response = $defaultAnswer;
        } else {
            $response = $textResponse;
        }

        return $response;
    }

    /**
     * @return never
     * @throws Exception
     */
    public function cli(
    ): never
    {
        system('clear');
        $this->projectName = $this->readInput(prompt: 'Project Name');

        if (array_key_exists('MINIMALISM_SERVICE_MYSQL', $_ENV) && $_ENV['MINIMALISM_SERVICE_MYSQL'] !== ''){
            $dbs = explode(',', $_ENV['MINIMALISM_SERVICE_MYSQL']);
            if (count($dbs) === 1){
                $this->databaseIdentifier = $dbs[0];
            } else {
                echo 'Available database:' . PHP_EOL;
                foreach ($dbs as $dbKey => $dbName){
                    echo '  ' . $dbKey . '. ' . $dbName . PHP_EOL;
                }

                while ($this->databaseIdentifier === null) {
                    $input = $this->readInput(prompt: 'Select database to import');
                    if (is_numeric($input) && $input <= count($dbs) - 1){
                        $this->databaseIdentifier = $dbs[$input];
                    } else {
                        echo 'Invalid selection';
                    }
                }
            }

            $this->database = new DatabaseObject(
                data: $this->data,
                projectName: $this->projectName,
                namespace: $this->minimaliser->getNamespace(),
                identifier: $this->databaseIdentifier,
            );

            $this->writeObjects(
                tables: $this->database->getTables(),
            );

            $this->writeTests(
                tables: $this->database->getTables(),
            );
        } else {
            echo 'No databases specified in the .env file.';
        }

        exit;
    }

    /**
     * @param array $tables
     * @return void
     * @throws Exception
     */
    private function writeObjects(
        array $tables,
    ): void
    {
        FileFactory::createFiles(
            projectName: $this->projectName,
            tables: $tables,
        );

        foreach ($tables as $table) {
            FileFactory::generateObjectFiles(
                table: $table,
            );
        }
    }

    /**
     * @param array $tables
     * @return void
     * @throws Exception
     */
    private function writeTests(
        array $tables,
    ): void
    {
        TestsFactory::createTestFiles(
            namespace: $this->minimaliser->getNamespace(),
            projectName: $this->projectName,
            databaseName: $this->databaseIdentifier,
            tables: $tables,
        );

        foreach ($tables as $table) {
            TestsFactory::generateDataFiles(
                namespace: $this->minimaliser->getNamespace(),
                projectName: $this->projectName,
                databaseName: $this->databaseIdentifier,
                table: $table,
            );

            if ($table->isComplete()) {
                TestsFactory::generateFunctionalTestFiles(
                    namespace: $this->minimaliser->getNamespace(),
                    projectName: $this->projectName,
                    databaseName: $this->databaseIdentifier,
                    table: $table,
                );
            }
        }
    }
}