<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Type;

interface TypeProvider
{
    /**
     * @param string $schema Type class-name
     *
     * @return Type
     */
    public function getType($schema);
}
