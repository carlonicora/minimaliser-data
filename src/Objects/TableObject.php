<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Factories\SqlQueryFactory;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data\TableDefinition\Databases\TableDefinitionTable;
use CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases\TablesTable;
use CarloNicora\Minimalism\MinimaliserData\Factories\Pluraliser;
use CarloNicora\Minimalism\MinimaliserData\Interfaces\MinimaliserObjectInterface;
use Exception;

class TableObject implements MinimaliserObjectInterface
{
    /** @var FieldObject[]  */
    private array $fields=[];

    /** @var FieldObject|null  */
    private ?FieldObject $primaryKey=null;

    /** @var string  */
    private string $tableName;

    /** @var string  */
    private string $objectName;

    /** @var string  */
    private string $objectNamePlural;

    /** @var bool  */
    private bool $isComplete=true;

    /** @var FieldObject[]  */
    private array $children=[];

    /**
     * @param SqlInterface $data
     * @param string $projectName
     * @param string $namespace
     * @param string $databaseIdentifier
     * @param string $name
     * @throws MinimalismException
     */
    public function __construct(
        private readonly SqlInterface $data,
        private readonly string $projectName,
        private readonly string $namespace,
        private readonly string $databaseIdentifier,
        private string $name,
    )
    {
        $this->tableName = $this->name;

        if (str_starts_with($this->name, '_')){
            $this->name = substr($this->name, 1);
        }

        $this->objectName = Pluraliser::singular(ucfirst($this->name));
        $this->objectNamePlural = Pluraliser::plural(ucfirst($this->name));

        do {
            $factory = SqlQueryFactory::create(
                tableClass: TableDefinitionTable::class,
                overrideDatabaseIdentifier: $databaseIdentifier,
            )->setSql('DESCRIBE `' . $this->tableName . '`;');

            $fields = $this->data->read(
                queryFactory: $factory,
            );

            $doesCreatedAtExists = false;
            $doesUpdatedAtExists = false;

            foreach ($fields as $field) {
                if ($field['Field'] === 'createdAt') {
                    $doesCreatedAtExists = true;
                }
                if ($field['Field'] === 'updatedAt') {
                    $doesUpdatedAtExists = true;
                }
            }

            if (!$doesCreatedAtExists || !$doesUpdatedAtExists){
                if (!$doesCreatedAtExists) {
                    $factory = SqlQueryFactory::create(
                        tableClass: TableDefinitionTable::class,
                        overrideDatabaseIdentifier: $databaseIdentifier,
                    )->setSql('ALTER TABLE `' . $this->tableName . '` ADD COLUMN `createdAt` timestamp NOT NULL;');

                    /** @noinspection UnusedFunctionResultInspection */
                    $this->data->read(
                        queryFactory: $factory,
                    );
                }

                if (!$doesUpdatedAtExists) {
                    $factory = SqlQueryFactory::create(
                        tableClass: TableDefinitionTable::class,
                        overrideDatabaseIdentifier: $databaseIdentifier,
                    )->setSql('ALTER TABLE `' . $this->tableName . '` ADD COLUMN `updatedAt` timestamp NOT NULL;');

                    /** @noinspection UnusedFunctionResultInspection */
                    $this->data->read(
                        queryFactory: $factory,
                    );
                }
            }

        } while ($doesCreatedAtExists === false && $doesUpdatedAtExists === false);

        foreach ($fields as $field){
            $newField = new FieldObject(
                table: $this,
                field: $field,
            );
            $this->fields[] = $newField;

            if ($newField->isPrimaryKey()){
                $this->primaryKey = $newField;
            }
        }
    }

    /**
     * @param TableObject[] $tables
     * @param string $databaseIdentifier
     * @return void
     * @throws MinimalismException
     */
    public function createForeignKeys(
        array $tables,
        string $databaseIdentifier,
    ): void
    {
        $factory = SqlQueryFactory::create(
            tableClass: TablesTable::class,
            overrideDatabaseIdentifier: $databaseIdentifier,
        )->setSql(
            'SELECT COLUMN_NAME,REFERENCED_TABLE_NAME' .
            ' FROM information_schema.KEY_COLUMN_USAGE' .
            ' WHERE TABLE_SCHEMA="' . explode(',', $_ENV[$this->databaseIdentifier])[3] . '"' .
            ' AND TABLE_NAME="' . $this->tableName . '"' .
            ' AND REFERENCED_COLUMN_NAME IS NOT NULL;');

        $foreignKeys = $this->data->read(
            queryFactory: $factory,
        );

        $hasFields = false;
        foreach ($this->fields as $field){
            if (!$field->isPrimaryKey() && !in_array($field->getName(), ['createdAt','updatedAt'])) {
                foreach ($foreignKeys ?? [] as $foreignKey) {
                    if ($foreignKey['COLUMN_NAME'] === $field->getName()) {
                        foreach ($tables as $table) {
                            if ($table->getName() === $foreignKey['REFERENCED_TABLE_NAME']) {
                                $field->setForeignKey(foreignKey: $table);
                                break 2;
                            }
                        }
                    }
                }

                if ($field->getForeignKey() === null){
                    $hasFields = true;
                }
            }
        }

        if (!$hasFields){
            $this->isComplete = false;
        }
    }

    /**
     * @return string
     */
    public function getName(
    ): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTableName(
    ): string
    {
        return $this->tableName;
    }

    /**
     * @return FieldObject[]
     */
    public function getFields(
    ): array
    {
        return $this->fields;
    }

    /**
     * @return FieldObject|null
     */
    public function getPrimaryKey(
    ): ?FieldObject
    {
        return $this->primaryKey;
    }

    /**
     * @param bool $limitToAttributes
     * @return ResourceObject
     * @throws Exception
     */
    public function generateResourceObject(
        bool $limitToAttributes=false,
    ): ResourceObject
    {
        $response = new ResourceObject(
            type: 'table',
            id: $this->name,
        );

        $response->attributes->add(name: 'project', value: $this->projectName);
        $response->attributes->add(name: 'namespace', value: $this->namespace);
        $response->attributes->add(name: 'databaseIdentifier', value: $this->databaseIdentifier);
        $response->attributes->add(name: 'tableName', value: $this->tableName);
        $response->attributes->add(name: 'objectNameLowercase', value: strtolower($this->objectName));
        $response->attributes->add(name: 'objectName', value: $this->objectName);
        $response->attributes->add(name: 'objectNamePlural', value: $this->objectNamePlural);
        $response->attributes->add(name: 'isComplete', value: $this->isComplete);
        $response->attributes->add(name: 'isManyToMany', value: $this->name !== $this->tableName);

        foreach ($this->fields as $field) {
            if ($field->isPrimaryKey()) {
                $response->attributes->add(name: 'primaryKey', value: $field->getName());
                $response->attributes->add(name: 'primaryKeyCapitalised', value: ucfirst($field->getName()));
                break;
            }
        }

        if (!$limitToAttributes || !$this->isComplete) {
            $response->relationship('fields')->resourceLinkage->forceResourceList(true);

            foreach ($this->fields as $field) {
                $response->relationship('fields')->resourceLinkage->addResource($field->generateResourceObject());

                if ($field->getForeignKey() !== null){
                    $response->relationship('parents')->resourceLinkage->forceResourceList(true);
                    $response->relationship('parents')->resourceLinkage->addResource(
                        resource: $field->getForeignKey()->generateResourceObject(limitToAttributes: true),
                    );
                }
            }

            if ($this->children !== []) {
                $response->relationship('children')->resourceLinkage->forceResourceList(true);
                foreach ($this->children as $childField) {
                    $response->relationship('children')->resourceLinkage->addResource(
                        $childField->getTable()->generateResourceObject(limitToAttributes: true)
                    );
                }
            }
        }

        return $response;
    }

    /**
     * @return string
     */
    public function getObjectName(
    ): string
    {
        return $this->objectName;
    }

    /**
     * @param string $objectName
     */
    public function setObjectName(
        string $objectName,
    ): void
    {
        $this->objectName = $objectName;
    }

    /**
     * @return string
     */
    public function getObjectNamePlural(
    ): string
    {
        return $this->objectNamePlural;
    }

    /**
     * @param string $objectNamePlural
     */
    public function setObjectNamePlural(
        string $objectNamePlural,
    ): void
    {
        $this->objectNamePlural = $objectNamePlural;
    }

    /**
     * @return bool
     */
    public function isComplete(
    ): bool
    {
        return $this->isComplete;
    }

    /**
     * @return void
     */
    public function setIsNotComplete(
    ): void
    {
        $this->isComplete = false;
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
     * @param FieldObject $field
     * @return void
     */
    public function addChild(
        FieldObject $field,
    ): void
    {
        $this->children[] = $field;
    }

    /**
     * @return FieldObject[]
     */
    public function getChildren(
    ): array
    {
        return $this->children;
    }

    public function isManyToMany(): bool {
        return $this->name !== $this->tableName;
    }
}