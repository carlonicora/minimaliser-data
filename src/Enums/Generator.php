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
    case CreatorValidators;
    case UpdaterValidators;
    case Models;
    case Caches;

    /**
     * @param TableObject $table
     * @return string
     */
    public function getFileName(
        TableObject $table,
    ): string
    {
        $response = match ($this){
            self::Builders => $table->getObjectName() . 'Builder',
            self::Databases => $table->getObjectNamePlural() . 'Table',
            self::DataObjects => $table->getObjectName(),
            self::Factories => $table->getObjectNamePlural() . 'factory',
            self::IO => $table->getObjectName() . 'IO',
            self::CreatorValidators => $table->getObjectName() . 'CreateValidator',
            self::UpdaterValidators => $table->getObjectName() . 'EditValidator',
            self::Models => $table->getObjectNamePlural(),
            self::Caches => $table->getObjectNamePlural() . 'CacheFactory',
        };

        return $response . '.php';
    }

    /**
     * @param TableObject $table
     * @return string
     */
    public function getDataSubfolder(
        TableObject $table,
    ): string
    {
        $response = match ($this){
            self::Builders, self::Databases, self::DataObjects, self::IO, self::Factories, self::Caches => 'Data' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . $this->name,
            self::Models => $this->name,
            self::CreatorValidators, self::UpdaterValidators => 'Data' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Validators',
        };

        return $response . DIRECTORY_SEPARATOR;
    }
}