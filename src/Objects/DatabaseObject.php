<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases\TablesTable;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlQueryFactory;

class DatabaseObject
{
    /** @var TableObject[]  */
    private array $tables=[];

    /**
     * @param SqlInterface $data
     * @param string $projectName
     * @param string $namespace
     * @param string $identifier
     * @throws MinimalismException
     */
    public function __construct(
        SqlInterface $data,
        string $projectName,
        string $namespace,
        string $identifier,
    )
    {
        $factory = SqlQueryFactory::create(
            tableClass: TablesTable::class,
            overrideDatabaseIdentifier: $identifier,
        )->setSql('SHOW TABLES;');

        /** @noinspection OneTimeUseVariablesInspection */
        $tables = $data->read(
            queryFactory: $factory,
        );

        foreach ($tables as $table){
            $tableName = array_values($table)[0];
            $this->tables[] = new TableObject(
                data: $data,
                projectName: $projectName,
                namespace: $namespace,
                databaseIdentifier: $identifier,
                name: $tableName,
            );
        }
    }

    /**
     * @return TableObject[]
     */
    public function getTables(
    ): array
    {
        return $this->tables;
    }
}