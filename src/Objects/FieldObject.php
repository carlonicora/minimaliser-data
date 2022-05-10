<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\MinimaliserData\Interfaces\MinimaliserObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldType;
use CarloNicora\Minimalism\Services\MySQL\Enums\SqlFieldType;
use Exception;

class FieldObject implements MinimaliserObjectInterface
{
    /** @var string  */
    private string $name;

    /** @var SqlFieldType  */
    private SqlFieldType $sqlFieldType;

    /** @var int|null  */
    private ?int $length=null;

    /** @var FieldType  */
    private FieldType $type;

    /** @var string  */
    private string $phpType;

    /** @var bool  */
    private bool $isNullable;
    
    /** @var FieldOption|null  */
    private ?FieldOption $option=null;

    /** @var TableObject|null  */
    private ?TableObject $foreignKey=null;

    /**
     * @param TableObject $table
     * @param array $field
     */
    public function __construct(
        private readonly TableObject $table,
        array $field,
    )
    {
        $this->name = $field['Field'];

        $length = null;
        $fieldTypePart = explode('(', $field['Type']);
        $fieldType = explode(' ', $fieldTypePart[0])[0];
        $type = strtolower($fieldType);
        if (count($fieldTypePart) > 1){
            $length = explode(')', $fieldTypePart[1])[0];
            if (!is_numeric($length)){
                $length = null;
            } else {
                $this->length = $length;
            }
        }

        $this->sqlFieldType = SqlFieldType::from($type);

        if ($this->sqlFieldType === SqlFieldType::timestamp) {
            $this->phpType = 'int';
        } elseif ($this->sqlFieldType === SqlFieldType::tinyint && $this->length === 1) {
            $this->phpType = 'bool';
        } else {
            $this->phpType = $this->sqlFieldType->getPhpType($length);
        }
        $this->type = $this->sqlFieldType->getFieldType();

        $this->isNullable = ($field['Null'] === 'YES');

        if ($field['Key'] === 'PRI'){
            if ($field['Extra'] === 'auto_increment'){
                $this->option = FieldOption::AutoIncrement;
            } else {
                $this->option = FieldOption::PrimaryKey;
            }
        }
    }

    /**
     * @return ResourceObject
     * @throws Exception
     */
    public function generateResourceObject(
    ): ResourceObject
    {
        $response = new ResourceObject(
            type: 'field',
            id: $this->name,
        );

        $response->attributes->add(name: 'isNullable', value: $this->isNullable);
        $response->attributes->add(name: 'phpType', value: $this->phpType);
        $response->attributes->add(name: 'type', value: $this->type->name);

        if ($this->sqlFieldType === SqlFieldType::timestamp){
            $response->meta->add(name: 'DbFieldType', value: 'DbFieldType::IntDateTime');
        }

        if ($this->option !== null) {
            $response->meta->add(name: 'option', value: $this->option->name);
        }
        $response->meta->add(name: 'capitalisedName', value: ucfirst($this->name));
        $response->meta->add(name: 'isId', value: $this->option === FieldOption::AutoIncrement);
        
        if ($this->foreignKey !== null && $this->foreignKey->isComplete()){
            $response->meta->add(name: 'isForeignKey', value: true);
            $response->relationship($this->foreignKey->getName())->resourceLinkage->addResource($this->foreignKey->getPrimaryKey()->generateResourceObject());
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(
    ): bool
    {
        return ($this->option === FieldOption::AutoIncrement || $this->option === FieldOption::PrimaryKey);
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
     * @param TableObject|null $foreignKey
     */
    public function setForeignKey(
        ?TableObject $foreignKey
    ): void
    {
        $foreignKey->addChild($this);
        $this->foreignKey = $foreignKey;
    }

    /**
     * @return TableObject
     */
    public function getTable(
    ): TableObject
    {
        return $this->table;
    }

    /**
     * @return TableObject|null
     */
    public function getForeignKey(
    ): ?TableObject
    {
        return $this->foreignKey;
    }
}