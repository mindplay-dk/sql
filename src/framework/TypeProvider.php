<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Type;

interface TypeProvider
{
    /**
     * @param string $type_name Type class-name
     *
     * @return Type
     */
    public function getType($type_name);
}
