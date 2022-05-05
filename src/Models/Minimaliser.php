<?php
namespace CarloNicora\Minimalism\MinimaliserData\Models;

use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\Enums\HttpCode;

class Minimaliser extends AbstractModel
{
    /**
     * @return HttpCode
     */
    public function cli(
    ): HttpCode
    {
        return HttpCode::Ok;
    }
}