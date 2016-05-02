<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\TypeProvider;
use RuntimeException;

/**
 * This class represents an INSERT query.
 */
class InsertQuery extends Query
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var mixed[][] list of record maps, where Column name => value
     */
    private $records = [];

    /**
     * @param Driver                 $driver
     * @param TypeProvider           $types
     * @param Table                  $table  Table to INSERT into
     * @param mixed[]|mixed[][]|null $record optional record map (or list of record maps) where Column name => value
     */
    public function __construct(Driver $driver, TypeProvider $types, Table $table, array $record = null)
    {
        parent::__construct($types);

        if ($table->getAlias()) {
            throw new RuntimeException("can't insert into a Table instance with an alias");
        }

        $this->driver = $driver;

        $this->table = $table;

        if ($record !== null) {
            $this->add($record);
        }
    }

    /**
     * Add one or more records to this INSERT query.
     *
     * @param mixed[]|mixed[][] $record record map (or list of record maps) where Column name => value
     */
    public function add(array $record)
    {
        reset($record);

        $first_key = key($record);

        if ($first_key === null) {
            return; // empty array given - no records added
        }

        if (is_array($record[$first_key])) {
            // append given list of records:
            $this->records = array_merge($this->records, $record);
        } else {
            // append given single record:
            $this->records[] = $record;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSQL()
    {
        if (count($this->records) === 0) {
            throw new RuntimeException("no records added to this query");
        }

        $table = "{$this->table}";

        /** @var Column[] $columns */

        $columns = array_filter(
            $this->table->listColumns(),
            function (Column $column) {
                return $column->isAuto() === false;
            }
        );

        $quoted_column_names = implode(
            ", ",
            array_map(
                function (Column $column) {
                    return $this->driver->quoteName($column->getName());
                },
                $columns
            )
        );

        $tuples = [];

        foreach ($this->records as $tuple_num => $record) {
            $placeholders = [];

            foreach ($columns as $col_index => $column) {
                $name = $column->getName();
                
                if (array_key_exists($name, $record)) {
                    $value = $record[$name];
                } elseif ($column->isRequired() === false) {
                    $value = $column->getDefault();
                } else {
                    throw new RuntimeException("required value '{$name}' missing from tuple # {$tuple_num}");
                }
                
                $placeholder = "c{$tuple_num}_{$col_index}";
                
                $placeholders[] = ":{$placeholder}";
                
                $this->bind($placeholder, $value, $column->getType());
            }

            $tuples[] = "(" . implode(", ", $placeholders) . ")";
        }

        return "INSERT INTO {$table} ({$quoted_column_names}) VALUES\n" . implode(",\n", $tuples);
    }
}
