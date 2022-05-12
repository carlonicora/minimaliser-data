<?php
namespace CarloNicora\Minimalism\MinimaliserData\Enums;

enum SharedFile
{
    case Dictionary;
    case AbstractDataObject;
    case AbstractResourceBuilder;
    case AbstractModel;
    case AbstractSqlIO;

    /**
     * @param string $projectName
     * @return string
     */
    public function getFileName(
        string $projectName,
    ): string
    {
        $response = match ($this){
            self::Dictionary => $projectName . 'Dictionary',
            self::AbstractDataObject => 'Abstract' . $projectName . 'DataObject',
            self::AbstractResourceBuilder => 'Abstract' . $projectName . 'ResourceBuilder',
            self::AbstractModel => 'Abstract' . $projectName . 'Model',
            self::AbstractSqlIO => 'Abstract' . $projectName . 'SqlIO',
        };

        return $response . '.php';
    }
}