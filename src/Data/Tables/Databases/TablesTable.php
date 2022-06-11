<?php
namespace CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases;


use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlFieldAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlTableAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldType;

#[SqlTableAttribute(name: 'tables', databaseIdentifier: '')]
enum TablesTable
{
    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case tableName;
}