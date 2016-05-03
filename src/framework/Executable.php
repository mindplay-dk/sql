<?php

namespace mindplay\sql\framework;

/**
 * This interface defines the aspect of e.g. `Statement` that makes it "executable", in the
 * sense it can create or provide a fully-populated SQL string and params, ready for preparation.
 *
 * @see Connection::prepare()
 * @see Connection::execute()
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
