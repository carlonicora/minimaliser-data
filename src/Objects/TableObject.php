<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\MinimaliserData\Data\TableDefinition\Databases\TableDefinitionTable;
use CarloNicora\Minimalism\MinimaliserData\Interfaces\MinimaliserObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlQueryFactory;
use Exception;

class TableObject implements MinimaliserObjectInterface
{
    /** @var FieldObject[]  */
    private array $fields=[];

    /** @var string  */
    private string $objectName;

    /** @var string  */
    private string $objectNamePlural;

    /** @var bool  */
    private bool $isComplete=true;

    /**
     * @param SqlInterface $data
     * @param string $projectName
     * @param string $namespace
     * @param string $databaseIdentifier
     * @param string $name
     * @throws MinimalismException
     */
    public function __construct(
        SqlInterface $data,
        private readonly string $projectName,
        private readonly string $namespace,
        private readonly string $databaseIdentifier,
        private readonly string $name,
    )
    {
        $this->objectName = ucfirst(strtolower($this->name));
        if (str_ends_with($this->objectName, 's')){
            $this->objectName = substr($this->objectName, 0, -1);
        }

        $this->objectNamePlural = $this->objectName . 's';

        $factory = SqlQueryFactory::create(
            tableClass: TableDefinitionTable::class,
            overrideDatabaseIdentifier: $databaseIdentifier,
        )->setSql('DESCRIBE ' . $this->name . ';');

        $fields = $data->read(
            queryFactory: $factory,
        );

        foreach ($fields as $field){
            $this->fields[] = new FieldObject($field);
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
     * @return ResourceObject
     * @throws Exception
     */
    public function generateResourceObject(
    ): ResourceObject
    {
        $response = new ResourceObject(
            type: 'table',
            id: $this->name,
        );

        $response->attributes->add(name: 'project', value: $this->projectName);
        $response->attributes->add(name: 'namespace', value: $this->namespace);
        $response->attributes->add(name: 'databaseIdentifier', value: $this->databaseIdentifier);
        $response->attributes->add(name: 'objectName', value: $this->objectName);
        $response->attributes->add(name: 'objectNamePlural', value: $this->objectNamePlural);
        $response->attributes->add(name: 'isComplete', value: $this->isComplete);

        foreach ($this->fields as $field) {
            if ($field->isPrimaryKey()){
                $response->attributes->add(name: 'primaryKey', value: $field->getName());
                break;
            }
        }

        $response->relationship('fields')->resourceLinkage->forceResourceList(true);
        foreach ($this->fields as $field) {
            $response->relationship('fields')->resourceLinkage->addResource($field->generateResourceObject());
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
}