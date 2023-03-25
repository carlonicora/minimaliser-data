<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Factories\SqlQueryFactory;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases\TablesTable;
use CarloNicora\Minimalism\MinimaliserData\Factories\Pluraliser;

class DatabaseObject
{
    /** @var TableObject[]  */
    private array $tables=[];

    /**
     * @param SqlInterface $data
     * @param string $projectName
     * @param string $namespace
     * @param string $identifier
     * @param string[] $loadedServices
     * @throws MinimalismException
     */
    public function __construct(
        SqlInterface $data,
        string $projectName,
        string $namespace,
        string $identifier,
        array $loadedServices,
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
                loadedServices: $loadedServices,
            );
        }

        foreach ($this->tables as $table){
            $table->createForeignKeys(
                tables: $this->tables,
                databaseIdentifier: $identifier,
            );
        }

        foreach ($this->tables as $table){
            foreach ($table->getFields() as $field) {
                if ($field->isForeignKey()) {
                    if ($table->isManyToMany()) {
                        $name = $table->getRelatedManyToManyTable($field);
                        $primaryKey = $table->getRelatedManyToManyPrimaryKey($field);
                        if ($name !== null) {
                            $this->getTable($field->getForeignKeyTable())?->addExternalForeignKeyTable([
                                'name' => $name,
                                'relationshipName' => $name,
                                'primaryKey' => $primaryKey,
                                'primaryKeyCapitalised' => ucfirst($primaryKey),
                                'manyToMany' => true,
                                'manyToManyObject' => $table->getObjectName(),
                                'manyToManyObjectPlural' => Pluraliser::plural($table->getObjectName()),
                                'tableExists' => $this->getTable($name) !== null,
                            ]);
                        }
                    } else {
                        $this->getTable($field->getForeignKeyTable())?->addExternalForeignKeyTable([
                            'name' => $table->getName(),
                            'relationshipName' => Pluraliser::singular($table->getName()),
                            'primaryKey' => $table->getPrimaryKey()?->getName(),
                            'manyToMany' => false,
                            'tableExists' => true,
                        ]);
                    }
                }
            }
        }
    }

    private function getTable(
        string $name,
    ): TableObject|null {
        foreach ($this->tables as $table) {
            if (strtolower($table->getName()) === strtolower($name)){
                return $table;
            }
        }

        return null;
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