<?php
namespace CarloNicora\Minimalism\MinimaliserData\Data\Tables\Databases;

use CarloNicora\Minimalism\Services\MySQL\Data\SqlField;
use CarloNicora\Minimalism\Services\MySQL\Data\SqlTable;

#[SqlTable(name: 'tables', databaseIdentifier: '')]
enum TablesTable
{
    #[SqlField]
    case tableName;
}