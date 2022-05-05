<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlQueryFactory;
use CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases\TablesTable;
use CarloNicora\Minimalism\MinimaliserData\Data\TableDefinition\Databases\TableDefinitionTable;

class Minimaliser extends AbstractModel
{
    /** @var string|null  */
    private ?string $selectedDatabase=null;

    /** @var string|null  */
    private ?string $selectedTable=null;

    /** @var SqlInterface  */
    private SqlInterface $data;

    /** @var array|null  */
    private ?array $tables=null;

    /** @var array|null  */
    private ?array $fields=null;

    /**
     * @param Data $minimaliser
     * @param SqlInterface $data
     * @return HttpCode
     * @throws MinimalismException
     */
    public function cli(
        Data $minimaliser,
        SqlInterface $data,
    ): HttpCode
    {
        $this->data = $data;

        $this->selectOrCreateMySqlConnection();

        $inOperation = true;

        while ($inOperation) {
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            $this->selectTable();
            echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
            $this->readFields();

            $this->generateBuilder();
            $this->generateDatabase();
            $this->generateDataObject();
            $this->generateFactory();
            $this->generateIO();
            $this->generateValidators();

            $inOperation = false;
        }

        exit;
        return HttpCode::Ok;
    }

    /**
     * @return void
     */
    private function selectOrCreateMySqlConnection(
    ): void
    {
        if (array_key_exists('MINIMALISM_SERVICE_MYSQL', $_ENV) && $_ENV['MINIMALISM_SERVICE_MYSQL'] !== ''){
            $dbs = explode(',', $_ENV['MINIMALISM_SERVICE_MYSQL']);
            echo 'Please select the database you want to source:' . PHP_EOL;
            foreach ($dbs as $dbKey => $dbName){
                echo ' ' . $dbKey . '. ' . $dbName . PHP_EOL;
            }

            echo 'Database to use: ';

            while ($this->selectedDatabase === null) {
                $input = substr(fgets(STDIN), 0, -(strlen(PHP_EOL)));
                if (is_numeric($input) || $input <= count($dbs) - 1){
                    $this->selectedDatabase = $dbs[$input];
                }
            }

            echo $this->selectedDatabase;
        } else {
            echo 'implement new connections';
            exit;
        }
    }

    /**
     * @return void
     * @throws MinimalismException
     */
    private function selectTable(
    ): void
    {
        if ($this->tables === null) {
            $factory = SqlQueryFactory::create(
                tableClass: TablesTable::class,
                overrideDatabaseIdentifier: $this->selectedDatabase,
            )
                ->setSql('SHOW TABLES;');

            $availableTables = $this->data->read(
                queryFactory: $factory,
            );

            $this->tables = [];

            foreach ($availableTables as $table) {
                $this->tables[] = array_values($table)[0];
            }
        }

        echo PHP_EOL . PHP_EOL . 'Select the table you want to import:' . PHP_EOL;

        foreach ($this->tables as $tableKey => $table){
            echo ' ' . $tableKey . '. ' . $table . PHP_EOL;
        }

        echo 'Table to import: ';

        while ($this->selectedTable === null) {
            $input = substr(fgets(STDIN), 0, -(strlen(PHP_EOL)));
            if (is_numeric($input) || $input <= count($this->tables) - 1){
                $this->selectedTable = $this->tables[$input];
            }
        }
    }

    /**
     * @return void
     * @throws MinimalismException
     */
    private function readFields(
    ): void
    {
        $factory = SqlQueryFactory::create(
            tableClass: TableDefinitionTable::class,
            overrideDatabaseIdentifier: $this->selectedDatabase,
        )
            ->setSql('DESCRIBE ' . $this->selectedTable . ';');

        $fields = $this->data->read(
            queryFactory: $factory,
        );

        $this->fields = [];

        foreach ($fields as $field){
            $newField = [];

            $newField['name'] = $field['Field'];
            $newField['type'] = match($field['Type']){
                'int', 'biging', 'bigint unsigned' => 'FieldType::Integer',
                default => 'FieldType::String',
            };
            if ($field['Key'] === 'PRI'){
                if ($field['Extra'] === 'auto_incremebt'){
                    $newField['option'] = 'FieldOption::AutoIncrement';
                } else {
                    $newField['option'] = 'FieldOption::PrimaryKey';
                }
            }

            $this->fields[] = $newField;
        }
    }

    /**
     * @return void
     */
    private function generateDatabase(
    ): void
    {

    }

    /**
     * @return void
     */
    private function generateDataObject(
    ): void
    {

    }

    /**
     * @return void
     */
    private function generateIO(
    ): void
    {

    }

    /**
     * @return void
     */
    private function generateFactory(
    ): void
    {

    }

    /**
     * @return void
     */
    private function generateBuilder(
    ): void
    {

    }

    /**
     * @return void
     */
    private function generateValidators(
    ): void
    {

    }
}