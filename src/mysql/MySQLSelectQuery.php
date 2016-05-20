<?php

namespace mindplay\sql\mysql;

use mindplay\sql\model\query\SelectQuery;

class MySQLSelectQuery extends SelectQuery
{
    const CALC_FOUND_ROWS = "SQL_CALC_FOUND_ROWS";

    /**
     * Applies the SQL_CALC_FOUND_ROWS flag to the query.
     * 
     * @link http://dev.mysql.com/doc/refman/5.7/en/information-functions.html#function_found-rows
     *
     * @return $this
     */
    public function calcFoundRows()
    {
        $this->setFlag(self::CALC_FOUND_ROWS);
        
        return $this;
    }
}
