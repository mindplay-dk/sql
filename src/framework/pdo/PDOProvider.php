<?php

namespace mindplay\sql\framework\pdo;

use InvalidArgumentException;
use PDO;

/**
 * This class implements a provider layer over PDO.
 *
 * You don't need to use this layer - the framework itself has no dependency on it, but you
 * may find it useful as a means of addressing certain issues with `PDO`.
 *
 * For one, `PDO` instances cannot be created without immediately opening the connection - this
 * provider does not construct `PDO` and open the connection until you ask for the `PDO` instance.
 *
 * Also, `PDO` has no ability to report properties like database-name, username, etc. if you need them.
 */
class PDOProvider
{
    const PROTOCOL_POSTGRES = "pgsql";
    const PROTOCOL_MYSQL    = "mysql";

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $dsn;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var PDO|null
     */
    private $pdo;
    
    /**
     * @param string      $protocol database protocol name (one of the PROTOCOL_* class constants)
     * @param string      $dbname   name of the database to connect to
     * @param string      $username
     * @param string      $password
     * @param array|null  $options  PDO constructor options
     * @param string|null $host     optional hostname; defaults to "localhost"
     * @param int|null    $port     optional port-number; defaults to the standard port-number for the given $db type
     */
    public function __construct($protocol, $dbname, $username, $password, $options = null, $host = null, $port = null)
    {
        static $default_port = [
            self::PROTOCOL_MYSQL    => 3306,
            self::PROTOCOL_POSTGRES => 5432,
        ];

        if (! isset($default_port[$protocol])) {
            throw new InvalidArgumentException("unsupported DBMS type: {$protocol}");
        }

        if ($port === null) {
            $port = $default_port[$protocol];
        }

        $this->protocol = $protocol;
        $this->dsn = "{$protocol}:host={$host};port={$port};dbname={$dbname}";
        $this->username = $username;
        $this->password = $password;
        $this->options = $options ?: [];
        $this->host = $host ?: "localhost";
        $this->port = $port;
    }

    /**
     * @return PDO
     */
    public function getPDO()
    {
        if (! isset($this->pdo)) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        }

        return $this->pdo;
    }

    /**
     * @return string database procol name (one of the PROTOCOL_* class constants)
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getDSN()
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }
}
