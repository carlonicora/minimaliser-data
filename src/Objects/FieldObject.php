<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldOption;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldType;
use CarloNicora\Minimalism\MinimaliserData\Enums\MySqlFieldType;
use CarloNicora\Minimalism\MinimaliserData\Factories\Pluraliser;
use CarloNicora\Minimalism\MinimaliserData\Interfaces\MinimaliserObjectInterface;
use Exception;

class FieldObject implements MinimaliserObjectInterface
{
    /** @var string  */
    private string $name;

    /** @var MySqlFieldType  */
    private MySqlFieldType $sqlFieldType;

    /** @var int|null  */
    private ?int $length=null;

    /** @var SqlFieldType  */
    private SqlFieldType $type;

    /** @var string  */
    private string $phpType;

    /** @var bool  */
    private bool $isNullable;

    /** @var bool  */
    private bool $isForeignKey=false;

    /** @var string|null  */
    private ?string $foreignKeyField=null;

    /** @var string|null  */
    private ?string $foreignKeyTable=null;
    
    /** @var SqlFieldOption|null  */
    private ?SqlFieldOption $option=null;

    /** @var TableObject|null  */
    private ?TableObject $foreignKey=null;

    /** @var array|null  */
    private ?array $enumValues=null;

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

        if (str_contains($this->name, '_')){
            $this->isForeignKey = true;
            $this->foreignKeyTable = substr($this->name, 0, strpos($this->name, '_'));
            $this->foreignKeyField = substr($this->name, strpos($this->name, '_') + 1);
        }

        $length = null;
        $fieldTypePart = explode('(', $field['Type']);
        $fieldType = explode(' ', $fieldTypePart[0], 2)[0];
        $type = strtolower($fieldType);
        if (count($fieldTypePart) > 1){
            $length = explode(')', $fieldTypePart[1], 2)[0];
            if (!is_numeric($length)){
                $length = null;
            } else {
                $this->length = $length;
            }
        }

        $this->sqlFieldType = MySqlFieldType::from($type);

        if ($this->sqlFieldType === MySqlFieldType::timestamp) {
            $this->phpType = 'int';
        } elseif ($this->sqlFieldType === MySqlFieldType::tinyint && $this->length === 1) {
            $this->phpType = 'bool';
        } elseif ($this->sqlFieldType === MySqlFieldType::enum){
            $this->phpType = 'string';
            $enums = explode(',', substr($fieldTypePart[1], 0, -1));
            $this->enumValues = [];
            foreach ($enums as $enumValue){
                $this->enumValues[] = substr($enumValue, 1, -1);
            }
        } else {
            $this->phpType = $this->sqlFieldType->getPhpType($length);
        }
        $this->type = $this->sqlFieldType->getFieldType();

        $this->isNullable = ($field['Null'] === 'YES');

        if ($field['Key'] === 'PRI'){
            if ($field['Extra'] === 'auto_increment'){
                $this->option = SqlFieldOption::AutoIncrement;
            } else {
                $this->option = SqlFieldOption::PrimaryKey;
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
            id: $this->table->getName() . '.' . $this->name,
        );

        $response->attributes->add(name: 'name', value: $this->name);
        $response->attributes->add(name: 'isNullable', value: $this->isNullable);
        $response->attributes->add(name: 'phpType', value: $this->phpType);
        $response->attributes->add(name: 'type', value: $this->type->name);

        if ($this->sqlFieldType === MySqlFieldType::timestamp){
            $response->meta->add(name: 'DbFieldType', value: 'DbFieldType::IntDateTime');
        } elseif ($this->phpType === 'bool'){
            $response->meta->add(name: 'DbFieldType', value: 'DbFieldType::Bool');
        }

        if ($this->option !== null) {
            $response->meta->add(name: 'option', value: $this->option->name);
        }
        $response->meta->add(name: 'capitalisedName', value: ucfirst($this->name));
        $response->meta->add(name: 'isId', value: $this->option === SqlFieldOption::AutoIncrement);

        if ($this->enumValues !== null){
            $response->meta->add('enumValues', $this->enumValues);
        }

        if ($this->isForeignKey){
            $metaForeignKey = [
                'table' => $this->foreignKeyTable,
                'tableSingular' => Pluraliser::singular($this->foreignKeyTable),
                'field' => $this->foreignKeyField,
                'fieldCapitalised' => ucfirst($this->foreignKeyField),
            ];

            $response->meta->add(name: 'FK', value: $metaForeignKey);
        }

        if ($this->foreignKey !== null && $this->foreignKey->isComplete()){
            $response->meta->add(name: 'isForeignKey', value: true);
            $response->meta->add(name: 'tableName', value: $this->foreignKey->getName());
            $response->meta->add(name: 'tableObjectName', value: $this->foreignKey->getObjectName());
            $response->meta->add(name: 'tableObjectNamePlural', value: $this->foreignKey->getObjectNamePlural());

            $response->relationship($this->foreignKey->getName())->resourceLinkage->addResource($this->foreignKey->getPrimaryKey()?->generateResourceObject());
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(
    ): bool
    {
        return ($this->option === SqlFieldOption::AutoIncrement || $this->option === SqlFieldOption::PrimaryKey);
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
     * @param TableObject $foreignKey
     */
    public function setForeignKey(
        TableObject $foreignKey
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

    /**
     * @return int|null
     */
    public function getLength(
    ): int|null
    {
        return $this->length;
    }

    public function isForeignKey(): bool {
        return $this->isForeignKey;
    }

    public function getForeignKeyTable(): string|null {
        return $this->foreignKeyTable;
    }

    public function getForeignKeyField(): string|null {
        return $this->foreignKeyField;
    }

    /**
     * @return string[]|null
     */
    public function getEnumValues(): array|null {
        return $this->enumValues;
    }
}