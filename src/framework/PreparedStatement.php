<?php

namespace mindplay\sql\framework;

/**
 * This interface defines the prepared SQL statement model.
 */
interface PreparedStatement
{
    /**
     * Bind an individual placeholder name to a given scalar (int|float|string|bool|null) value.
     *
     * @param string                     $name  placeholder name
     * @param int|float|string|bool|null $value scalar value
     *
     * @return void
     */
    public function bind($name, $value);

    /**
     * Executes the underlying SQL statement.
     *
     * @return void
     *
     * @throws SQLException on failure to execute the underlying SQL statement
     */
    public function execute();

    /**
     * Fetches the next record from the result set and advances the cursor.
     *
     * @return array|null next record-set (or NULL, if no more records are available)
     */
    public function fetch();

    /**
     * @return int number of rows affected by a non-returning query (e.g. INSERT, UPDATE or DELETE)
     */
    public function getRowsAffected();
}
