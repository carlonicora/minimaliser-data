<?php
namespace CarloNicora\Minimalism\MinimaliserData\Data\TableDefinition\Databases;

use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlFieldAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Attributes\SqlTableAttribute;
use CarloNicora\Minimalism\Interfaces\Sql\Enums\SqlFieldType;

#[SqlTableAttribute(name: 'tableDefinition', databaseIdentifier: '')]
enum TableDefinitionTable
{
    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Field;

    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Type;

    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Null;

    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Key;

    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Default;

    #[SqlFieldAttribute(fieldType: SqlFieldType::String)]
    case Extra;
}