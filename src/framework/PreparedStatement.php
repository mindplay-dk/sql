<?php

namespace mindplay\sql\framework;

use mindplay\sql\exceptions\SQLException;

/**
 * This interface defines the prepared SQL statement model.
 */
interface PreparedStatement
{
    /**
     * Bind an individual placeholder name to a given scalar (int|float|string|bool|null) value.
     *
     * @param $name  placeholder name
     * @param $value scalar value
     */
    public function bind(string $name, int|float|string|bool|null $value): void;

    /**
     * Executes the underlying SQL statement.
     *
     * @throws SQLException on failure to execute the underlying SQL statement
     */
    public function execute(): void;

    /**
     * Fetches the next record from the result set and advances the cursor.
     *
     * @return array<string,mixed>|null next record-set (or NULL, if no more records are available)
     */
    public function fetch(): array|null;

    /**
     * @return int number of rows affected by a non-returning query (e.g. INSERT, UPDATE or DELETE)
     */
    public function getRowsAffected(): int;
}
