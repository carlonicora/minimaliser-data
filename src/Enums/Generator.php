<?php
namespace CarloNicora\Minimalism\MinimaliserData\Enums;

use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;

Enum Generator
{
    case Builders;
    case Databases;
    case DataObjects;
    case Factories;
    case IO;
    case Validators;

    /**
     * @param TableObject $table
     * @return string
     */
    public function getFileName(
        TableObject $table,
    ): string
    {
        $response = match ($this){
            self::Builders => $table->getObjectNamePlural() . 'Builder',
            self::Databases => $table->getObjectNamePlural() . 'Table',
            self::DataObjects => $table->getObjectName(),
            self::Factories => $table->getObjectNamePlural() . 'factory',
            self::IO => $table->getObjectName() . 'IO',
            self::Validators => $table->getObjectName() . 'Validator',
        };

        return $response . '.php';
    }
}