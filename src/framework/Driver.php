<?php

namespace mindplay\sql\framework;

/**
 * This class implements a driver model for DBMS-specific operations.
 */
abstract class Driver
{
    /**
     * @param string $name table or column name
     *
     * @return string quoted name
     */
    abstract public function quoteName($name);
}
