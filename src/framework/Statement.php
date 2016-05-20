<?php

namespace mindplay\sql\framework;

/**
 * This interface enables a model to create or provide a fully-populated SQL string and matching
 * parameters, ready for preparation and/or execution by a `Connection` object.
 *
 * @see Connection::prepare()
 * @see Connection::execute()
 */
interface Statement
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
