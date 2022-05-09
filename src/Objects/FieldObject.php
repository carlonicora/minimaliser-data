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

    /** @var FieldType  */
    private FieldType $type;

    /** @var string  */
    private string $phpType;

    /** @var bool  */
    private bool $isNullable;
    
    /** @var FieldOption|null  */
    private ?FieldOption $option=null;

    /**
     * @param array $field
     */
    public function __construct(
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
            }
        }

        $this->phpType = SqlFieldType::from($type)->getPhpType($length);
        $this->type = SqlFieldType::from($type)->getFieldType();

        $this->isNullable = $field['NULL'] === 'YES';

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

        $response->meta->add(name: 'option', value: $this->option->name);
        $response->meta->add(name: 'capitalisedName', value: ucfirst($this->name));
        $response->meta->add(name: 'isId', value: $this->option === FieldOption::AutoIncrement);


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
}