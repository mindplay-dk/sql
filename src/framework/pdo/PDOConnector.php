<?php

namespace mindplay\sql\framework\pdo;

use PDO;

// TODO initialization option? e.g. `callback function(PDO $pdo): void`
// TODO add connection name? e.g. for display purposes
// TODO do we need to reflect username/password/options? or is the DSN enough?
// TODO add `Connector` interface for abstraction?

/**
 * This class implements a connector layer over `PDO`.
 *
 * You don't need to use this layer - the framework itself has no dependency on it, but you
 * may find it useful as a means of addressing certain issues with `PDO`:
 *
 *   1. `PDO` instances cannot be created without immediately opening the connection - this
 *      class does not construct `PDO` and open the connection until you ask for it.
 *
 *   2. `PDO` instances cannot be created more than once from the configuration - this class
 *      lets you create as many `PDO` instances as needed.
 *
 *   3. `PDO` instances do not reflect any of their initialization arguments - this class
 *      provides accessor for the database-name, username, etc. if you need them.
 *
 * If you do use PDO connectors, using them in your (DI container etc.) bootstrapping is
 * the recommended approach - because they are service locators in some sense, you should
 * avoid depending on them unless you actually need to read initialization properties or
 * create multiple instances, etc.
 */
class PDOConnector
{
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
     * Use the bare constructor only if you need to customize the DSN - use
     * one of the factory-methods for a typical PostgreSQL or MySQL DSN instead.
     *
     * @param string $dsn     PDO DSN (connection string)
     * @param string $username
     * @param string $password
     * @param array  $options PDO options
     *
     * @see forPostgreSQL() factory-method for a PostgreSQL connector
     * @see forMySQL() factory-method for a MySQL connector
     */
    public function __construct($dsn, $username, $password, $options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * Factory-method for an instance with a valid PostgreSQL DSN.
     *
     * For available `$options`, refer to this page:
     *
     * https://www.php.net/manual/en/pdo.setattribute.php
     *
     * @param string $host     host name (or localhost, dotted IP address, etc.)
     * @param int    $port     port number (typically 5432)
     * @param string $dbname   logical database name
     * @param string $username
     * @param string $password
     * @param array  $options
     *
     * @return self
     */
    public static function forPostgreSQL($host, $port, $dbname, $username, $password, $options = [])
    {
        return new self(
            "pgsql:host={$host};port={$port};dbname={$dbname}",
            $username,
            $password,
            $options
        );
    }

    /**
     * Factory-method for an instance with a valid MySQL DSN with a standard UTF-8 encoding.
     *
     * For available MySQL-specific `$options`, refer to this page:
     *
     * https://www.php.net/manual/en/ref.pdo-mysql.php#pdo-mysql.constants
     *
     * For available general `$options`, refer to this page:
     *
     * https://www.php.net/manual/en/pdo.setattribute.php
     *
     * For notes regarding charsets and Unicode encoding specifically, see also:
     *
     * https://dev.mysql.com/doc/refman/8.0/en/charset-unicode.html
     *
     * @param string $host     host name (or localhost, dotted IP address, etc.)
     * @param int    $port     port number (typically 5432)
     * @param string $dbname   logical database name
     * @param string $username
     * @param string $password
     * @param array  $options
     * @param string $charset  defaults to "utf8mb4" (see doc-block for notes)
     *
     * @return self
     */
    public static function forMySQL($host, $port, $dbname, $username, $password, $options = [], $charset = "utf8mb4")
    {
        return new self(
            "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
            $username,
            $password,
            $options
        );
    }

    /**
     * @return PDO
     */
    public function connect()
    {
        return new PDO($this->dsn, $this->username, $this->password, $this->options);
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
}
