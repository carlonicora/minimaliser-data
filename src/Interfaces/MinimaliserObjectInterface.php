<?php
namespace CarloNicora\Minimalism\MinimaliserData\Interfaces;

use CarloNicora\JsonApi\Objects\ResourceObject;

interface MinimaliserObjectInterface
{
    /**
     * @return ResourceObject
     */
    public function generateResourceObject(
    ): ResourceObject;
}