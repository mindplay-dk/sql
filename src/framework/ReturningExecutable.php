<?php

namespace mindplay\sql\framework;

/**
 * This interface expands upon the concept of {@see Executable} adding support for
 * Mappers to be applied while fetching the returned records of executing a returning
 * query, e.g. `SELECT` or `UPDATE RETURNING`.
 * 
 * @see Connection::fetch()
 */
interface ReturningExecutable extends Executable
{
    /**
     * @return Mapper[] list of Mappers to apply while fetching returned records
     */
    public function getMappers();
}
