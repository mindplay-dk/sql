<?php

namespace mindplay\sql\framework;

use PDO;

/**
 * This internal factory class is responsible for the creation of a bound and prepared
 * `PDOStatement` or `Result` instance from a given `Statement` instance.
 */
class Preparator
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Prepare and bind a `Statement` and create a `Result` instance.
     *
     * @param Executable $statement
     * @param int        $batch_size batch-size (when fetching large result sets)
     * @param array      $mappers    list of Mappers to apply while fetching results
     *
     * @return Result
     */
    public function prepareResult(Executable $statement, $batch_size, array $mappers)
    {
        return new Result($this->prepareStatement($statement), $batch_size, $mappers);
    }

    /**
     * Prepare and bind a `Statement` and create a prepared `PDOStatement` handle.
     *
     * @param Executable $statement
     *
     * @return PreparedStatement
     */
    public function prepareStatement(Executable $statement)
    {
        $params = $statement->getParams();

        $sql = $this->expandPlaceholders($statement->getSQL(), $params);

        $prepared_statement = new PreparedStatement($this->pdo->prepare($sql));

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $index = 1; // use a base-1 offset consistent with expandPlaceholders()

                foreach ($value as $item) {
                    // NOTE: we deliberately ignore the array indices here, as using them could result in broken SQL!

                    $prepared_statement->bind("{$name}_{$index}", $item);

                    $index += 1;
                }
            } else {
                $prepared_statement->bind($name, $value);
            }
        }

        return $statement;
    }

    /**
     * Internally expand SQL placeholders (for array-types)
     *
     * @param string $sql    SQL with placeholders
     * @param array  $params placeholder name/value pairs
     *
     * @return string SQL with expanded placeholders
     */
    private function expandPlaceholders($sql, array $params)
    {
        $replace_pairs = [];

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                // TODO: QA! For empty arrays, the resulting SQL is e.g.: "SELECT * FROM foo WHERE foo.bar IN (null)"

                $replace_pairs[":{$name}"] = count($value) === 0
                    ? "(null)" // empty set
                    : "(" . implode(', ', array_map(function ($i) use ($name) {
                        return ":{$name}_{$i}";
                    }, range(1, count($value)))) . ")";
            }
        }

        return count($replace_pairs)
            ? strtr($sql, $replace_pairs)
            : $sql; // no arrays found in the given parameters
    }
}
