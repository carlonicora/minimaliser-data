<?php
namespace CarloNicora\Minimalism\MinimaliserData\Data\TableDefinition\Databases;

use CarloNicora\Minimalism\Services\MySQL\Data\SqlField;
use CarloNicora\Minimalism\Services\MySQL\Data\SqlTable;

#[SqlTable(name: 'tableDefinition', databaseIdentifier: '')]
enum TableDefinitionTable
{
    #[SqlField]
    case Field;

    #[SqlField]
    case Type;

    #[SqlField]
    case Null;

    #[SqlField]
    case Key;

    #[SqlField]
    case Default;

    #[SqlField]
    case Extra;
}