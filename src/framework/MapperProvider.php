<?php

namespace mindplay\sql\framework;

/**
 * This interface enables an {@see Executable} to provide `Mapper` instances to be applied
 * while fetching the result of a returning query, e.g. `SELECT` or `UPDATE RETURNING`.
 * 
 * @see Connection::fetch()
 */
interface MapperProvider
{
    /**
     * @return Mapper[] list of Mappers to apply while fetching returned records
     */
    public function getMappers(): array;
}
