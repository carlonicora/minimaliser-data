<?php
namespace CarloNicora\Minimalism\MinimaliserData\Objects;

use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\MinimaliserData\Interfaces\MinimaliserObjectInterface;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldOption;
use CarloNicora\Minimalism\Services\MySQL\Enums\FieldType;
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

        switch ($field['Type']){
            case 'int':
            case 'bigint':
            case 'bigint unisgned':
                $this->type = FieldType::Integer;
                $this->phpType = 'int';
                break;
            default:
                $this->type = FieldType::String;
                $this->phpType = 'string';
                break;
        }

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

        return $response;
    }
}