<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Query;
use mindplay\sql\framework\TypeProvider;

/**
 * This class represents a custom SQL Query.
 */
class SQLQuery extends Query
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @param TypeProvider $types
     * @param string       $sql SQL statement (with placeholders)
     */
    public function __construct(TypeProvider $types, $sql)
    {
        parent::__construct($types);
        
        $this->sql = $sql;
    }
    
    /**
     * @inheritdoc
     */
    public function getSQL()
    {
        return $this->sql;
    }
}
