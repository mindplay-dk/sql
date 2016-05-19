<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\TypeProvider;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\components\ReturnVars;

/**
 * Abstract base class for Query types that return and map results, such as `SELECT` or `UPDATE RETURNING`.
 */
abstract class ReturningQuery extends ProjectionQuery implements MapperProvider
{
    use Mappers {
        getMappers as private getAddedMappers;
    }

    /**
     * @var ReturnVars
     */
    private $return_vars;

    /**
     * @param Table        $root
     * @param Driver       $driver
     * @param TypeProvider $types
     */
    public function __construct(Table $root, Driver $driver, TypeProvider $types)
    {
        parent::__construct($root, $driver, $types);

        $this->return_vars = new ReturnVars($root, $driver, $types);
    }

    /**
     * Add all the Columns of a full Table to be selected and returned
     * 
     * @param Table $table Table to select and return
     *                     
     * @return $this
     */
    public function table(Table $table)
    {
        $this->return_vars->addTable($table);

        return $this;
    }
    
    /**
     * Add one or more Columns to select and return
     * 
     * @param Column|Column[] one or more Columns to select and return
     *
     * @return $this
     */
    public function columns($cols)
    {
        $this->return_vars->addColumns($cols);

        return $this;
    }

    /**
     * Add an SQL expression to select and return
     * 
     * @param string           $expr return expression
     * @param string|null      $name return variable name (optional, but usually required)
     * @param Type|string|null $type optional Type (or Type class-name)
     *
     * @return $this
     */
    public function value($expr, $name = null, $type = null)
    {
        $this->return_vars->addValue($expr, $name, $type);

        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getMappers()
    {
        return array_merge([$this->return_vars->createTypeMapper()], $this->getAddedMappers());
    }

    /**
     * @return string comma-separated return expressions (for use in the SELECT or RETURNING clause of an SQL query)
     */
    protected function buildReturnVars()
    {
        return $this->return_vars->buildReturnVars();
    }
}
