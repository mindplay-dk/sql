<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Type;

/**
 * This interface defines an internal facet of the DatabaseContainer as a provider
 * of arbitrary Type objects.
 */
interface TypeProvider
{
    /**
     * @param string $type_name Type class-name
     *
     * @return Type
     */
    public function getType($type_name);
}
