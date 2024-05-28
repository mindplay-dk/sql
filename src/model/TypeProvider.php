<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Type;

/**
 * This interface defines an internal facet of the DatabaseContainer as a provider
 * of arbitrary Type objects.
 */
interface TypeProvider
{
    /**
     * @param $type_name type name (often a Type class-name)
     */
    public function getType(string $type_name): Type;

    /**
     * @param $type_name type name (often a Type class-name)
     */
    public function hasType(string $type_name): bool;
}
