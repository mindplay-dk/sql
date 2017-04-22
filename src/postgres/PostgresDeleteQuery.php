<?php

namespace mindplay\sql\postgres;

use mindplay\sql\framework\IndexerProvider;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\model\components\Returning;
use mindplay\sql\model\components\ReturnVars;
use mindplay\sql\model\Driver;
use mindplay\sql\model\query\DeleteQuery;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\TypeProvider;

class PostgresDeleteQuery extends DeleteQuery implements MapperProvider, IndexerProvider
{
    use Returning;

    /**
     * @param Driver       $driver
     * @param TypeProvider $types
     * @param Table        $table
     */
    public function __construct(Driver $driver, TypeProvider $types, Table $table)
    {
        parent::__construct($table, $driver, $types);

        $this->return_vars = new ReturnVars($table, $driver, $types);
    }

    /**
     * @inheritdoc
     */
    public function getSQL()
    {
        $returning = $this->return_vars->hasReturnVars()
            ? "\nRETURNING " . $this->return_vars->buildReturnVars()
            : "";
        
        return parent::getSQL() . $returning;
    }
}
