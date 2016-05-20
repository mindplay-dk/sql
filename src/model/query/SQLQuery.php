<?php

namespace mindplay\sql\model\query;

use mindplay\sql\framework\MapperProvider;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\TypeProvider;

/**
 * This class represents a custom SQL Query.
 */
class SQLQuery extends Query implements MapperProvider
{
    use Mappers;
    
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

    /**
     * @inheritdoc
     */
    public function getMappers()
    {
        return $this->mappers;
    }
}
