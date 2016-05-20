<?php

namespace mindplay\sql\framework;

/**
 * This interface indicates the ability of a model to generate a `SELECT COUNT(*)` statement.
 * 
 * @see Connection::count()
 */
interface Countable
{
    /**
     * @return Executable
     */
    public function createCountStatement();
}
