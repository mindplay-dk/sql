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

    private string $protocol;
    
    private string $dsn;
    
    private string $username;
    
    private string $password;
    
    /**
     * @var array<int,mixed>
     */
    private array $options;
    
    private string $host;
    
    private int $port;
    
    private PDO|null $pdo = null;
    
    /**
     * @param string                 $protocol database protocol name (one of the PROTOCOL_* class constants)
     * @param string                 $dbname   name of the database to connect to
     * @param string                 $username
     * @param string                 $password
     * @param array<int,mixed>|null  $options  PDO constructor options (attributes)
     * @param string|null            $host     optional hostname; defaults to "localhost"
     * @param int|null               $port     optional port-number; defaults to the standard port-number for the given $db type
     */
    public function __construct(string $protocol, string $dbname, string $username, string $password, array|null $options = null, string|null $host = null, int|null $port = null)
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

        if ($host === null) {
            $host = "localhost";
        }

        $this->protocol = $protocol;
        $this->dsn = "{$protocol}:host={$host};port={$port};dbname={$dbname}";
        $this->username = $username;
        $this->password = $password;
        $this->options = $options ?: [];
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        if (! isset($this->pdo)) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        }

        return $this->pdo;
    }

    /**
     * @return string database procol name (one of the PROTOCOL_* class constants)
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getDSN(): string
    {
        return $this->dsn;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array<int,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
