<?php

namespace mindplay\sql\framework;

/**
 * This interface defines the aspect of e.g. `Statement` that makes it "executable", in the
 * sense it can create or provide a complete SQL string (with PDO placeholders) and an `array` of
 * matching parameters.
 *
 * @see Connection::execute()
 * @see Connection::fetch()
 * @see Connection::prepare()
 * 
 * @see Preparator::prepareResult()
 * @see Preparator::prepareStatement()
 */
interface Executable
{
    /**
     * @return string SQL statement (with placeholders)
     */
    public function getSQL();

    /**
     * @return array map where placeholder name maps to a scalar value, or arrays of scalar values
     */
    public function getParams();
}
