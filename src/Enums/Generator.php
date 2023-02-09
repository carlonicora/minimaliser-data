<?php
namespace CarloNicora\Minimalism\MinimaliserData\Enums;

use CarloNicora\Minimalism\MinimaliserData\Objects\TableObject;

Enum Generator
{
    case Builders;
    case AbstractBuilders;
    case Databases;
    case DataObjects;
    case Factories;
    case IO;
    case AbstractIO;
    case CreatorValidators;
    case UpdaterValidators;
    case Models;
    case Caches;
    case AbstractCaches;
    case ChildModels;

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
            self::AbstractBuilders => 'Abstract' . $table->getObjectName() . 'Builder',
            self::Databases => $table->getObjectNamePlural() . 'Table',
            self::DataObjects => $table->getObjectName(),
            self::Factories => $table->getObjectNamePlural() . 'factory',
            self::IO => $table->getObjectName() . 'IO',
            self::AbstractIO => 'Abstract' . $table->getObjectName() . 'IO',
            self::CreatorValidators => $table->getObjectName() . 'CreateValidator',
            self::UpdaterValidators => $table->getObjectName() . 'EditValidator',
            self::Models, self::ChildModels => $table->getObjectNamePlural(),
            self::Caches => $table->getObjectNamePlural() . 'CacheFactory',
            self::AbstractCaches => 'Abstract' . $table->getObjectNamePlural() . 'CacheFactory',
        };

        return $response . '.php';
    }

    public function overrides(
    ): bool {
        return match($this) {
            self::Builders, self::Caches, self::IO, self::Models, self::ChildModels => false,
            default => true,
        };
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
            self::AbstractBuilders, self::AbstractIO, self::AbstractCaches => 'Data' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Abstracts',
            self::Builders, self::Databases, self::DataObjects, self::IO, self::Factories, self::Caches => 'Data' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . $this->name,
            self::Models => $this->name,
            self::CreatorValidators, self::UpdaterValidators => 'Data' . DIRECTORY_SEPARATOR . $table->getObjectNamePlural() . DIRECTORY_SEPARATOR . 'Validators',
            self::ChildModels => self::Models->name . DIRECTORY_SEPARATOR . $table->getObjectNamePlural(),
        };

        return $response . DIRECTORY_SEPARATOR;
    }
}