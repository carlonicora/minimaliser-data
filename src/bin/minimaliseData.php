<?php
/** @noinspection PhpIncludeInspection */
require_once '../../../../../vendor/autoload.php';

use CarloNicora\Minimalism\MinimaliserData\Models\Minimaliser;
use CarloNicora\Minimalism\Minimalism;

$minimalism = new Minimalism();
$minimalism->render(
    modelName: Minimaliser::class
);