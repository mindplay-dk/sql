<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Schema;

interface SchemaProvider
{
    /**
     * @param string $name Schema class-name
     *
     * @return Schema
     */
    public function getSchema($name);
}
