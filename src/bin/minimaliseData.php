<?php
$fileName = 'vendor/autoload.php';

do {
    if (file_exists($fileName)){
        require_once($fileName);
        break;
    }
    $fileName = '../' . $fileName;
} while (true);

use CarloNicora\Minimalism\Minimalism;
use CarloNicora\Minimalism\MinimaliserData\Models\Minimaliser;

$minimalism = new Minimalism();
$minimalism->render(
    modelName: Minimaliser::class
);