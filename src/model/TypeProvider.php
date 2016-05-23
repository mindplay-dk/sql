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
     * @param string $type_name type name (often a Type class-name)
     *
     * @return Type
     */
    public function getType($type_name);

    /**
     * @param string $type_name type name (often a Type class-name)
     *
     * @return bool
     */
    public function hasType($type_name);
}
