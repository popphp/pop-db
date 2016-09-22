<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter;

/**
 * SQLite database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Sqlite extends AbstractAdapter
{

    /**
     * Last SQL query
     * @var string
     */
    protected $lastSql = null;

    /**
     * Last result
     * @var resource
     */
    protected $lastResult;

    /**
     * Constructor
     *
     * Instantiate the SQLite database connection object using SQLite3
     *
     * @param  array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['database'])) {
            $this->throwError('Error: The database file was not passed.');
        } else if (!file_exists($options['database'])) {
            $this->throwError('Error: The database file does not exists.');
        }

        $flags = (isset($options['flags'])) ? $options['flags'] : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $key   = (isset($options['key']))   ? $options['key']   : null;

        $this->connection = new \SQLite3($options['database'], $flags, $key);
    }

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return Sqlite
     */
    public function query($sql)
    {
        $this->lastSql = (stripos($sql, 'select') !== false) ? $sql : null;

        if (!($this->result = $this->connection->query($sql))) {
            $this->throwError('Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg());
        }
        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @return Sqlite
     */
    public function prepare($sql)
    {
        $this->statement = $this->connection->prepare($sql);
        return $this;
    }

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return Sqlite
     */
    public function bindParams(array $params)
    {
        foreach ($params as $dbColumnName => $dbColumnValue) {
            ${$dbColumnName} = $dbColumnValue;
            $this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName});
        }
        return $this;
    }

    /**
     * Bind a parameter for a prepared SQL query
     *
     * @param  mixed $param
     * @param  mixed $value
     * @param  int   $type
     * @return Sqlite
     */
    public function bindParam($param, $value, $type = SQLITE3_BLOB)
    {
        $this->statement->bindParam($param, $value, $type);
        return $this;
    }

    /**
     * Bind a value for a prepared SQL query
     *
     * @param  mixed $param
     * @param  mixed $value
     * @param  int   $type
     * @return Sqlite
     */
    public function bindValue($param, $value, $type = SQLITE3_BLOB)
    {
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Sqlite
     */
    public function execute()
    {
        if (null === $this->statement) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->result = $this->statement->execute();
        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    public function fetch()
    {
        if (null === $this->result) {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return $this->result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll()
    {
        $rows = [];

        while (($row = $this->fetch())) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->connection->close();
        }

        parent::disconnect();
    }

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    public function escape($value)
    {
        return $this->connection->escapeString($value);;
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId()
    {
        return $this->connection->lastInsertRowID();
    }

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        if (null === $this->lastSql) {
            return $this->connection->changes();
        } else {
            if (!($this->lastResult = $this->connection->query($this->lastSql))) {
                $this->throwError('Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg());
            } else {
                $num = 0;
                while (($row = $this->lastResult->fetcharray(SQLITE3_ASSOC)) != false) {
                    $num++;
                }
                return $num;
            }
        }
    }
    
    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = $this->connection->version();
        return 'SQLite ' . $version['versionString'];
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
    {
        $tables = [];
        $sql = "SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%' UNION ALL SELECT name FROM sqlite_temp_master WHERE type IN ('table', 'view') ORDER BY 1";

        $this->query($sql);
        while (($row = $this->fetch())) {
            $tables[] = $row['name'];
        }

        return $tables;
    }

}