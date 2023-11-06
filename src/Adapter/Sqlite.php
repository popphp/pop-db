<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Sqlite extends AbstractAdapter
{

    /**
     * SQLite flags
     * @var ?int
     */
    protected ?int $flags = null;

    /**
     * SQLite key
     * @var ?string
     */
    protected ?string $key = null;

    /**
     * Last SQL query
     * @var ?string
     */
    protected ?string $lastSql = null;

    /**
     * Last result
     * @var mixed
     */
    protected mixed $lastResult = null;

    /**
     * Constructor
     *
     * Instantiate the SQLite database connection object using SQLite3
     *
     * @param  array $options
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->connect($options);
        }
    }

    /**
     * Connect to the database
     *
     * @param  array $options
     * @return Sqlite
     */
    public function connect(array $options = []): Sqlite
    {
        if (!empty($options)) {
            $this->setOptions($options);
        } else if (!$this->hasOptions()) {
            $this->throwError('Error: The database file was not passed.');
        } else if (!$this->dbFileExists()) {
            $this->throwError("Error: The database file '" . $this->options['database'] . "'does not exists.");
        }

        $this->connection = new \SQLite3($this->options['database'], $this->flags, (string)$this->key);

        return $this;
    }

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return Sqlite
     */
    public function setOptions(array $options): Sqlite
    {
        $this->options = $options;

        if (!$this->hasOptions()) {
            $this->throwError('Error: The database file was not passed.');
        } else if (!$this->dbFileExists()) {
            $this->throwError("Error: The database file '" . $this->options['database'] . "'does not exists.");
        }

        $this->flags = (isset($this->options['flags'])) ? $this->options['flags'] : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $this->key   = (isset($this->options['key']))   ? $this->options['key']   : null;

        return $this;
    }

    /**
     * Has database connection options
     *
     * @return bool
     */
    public function hasOptions(): bool
    {
        return (isset($this->options['database']));
    }

    /**
     * Does the database file exist
     *
     * @return bool
     */
    public function dbFileExists(): bool
    {
        return (isset($this->options['database']) && file_exists($this->options['database']));
    }

    /**
     * Begin a transaction
     *
     * @return Sqlite
     */
    public function beginTransaction(): Sqlite
    {
        $this->query('BEGIN TRANSACTION');
        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Sqlite
     */
    public function commit(): Sqlite
    {
        $this->query('COMMIT');
        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @return Sqlite
     */
    public function rollback(): Sqlite
    {
        $this->query('ROLLBACK');
        return $this;
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Sqlite
     */
    public function query(mixed $sql): Sqlite
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->lastSql = (stripos($sql, 'select') !== false) ? $sql : null;

        if (!($this->result = $this->connection->query($sql))) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
            }
            $this->throwError('Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg());
        } else if ($this->profiler !== null) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        if ($this->profiler !== null) {
            $this->profiler->current->finish();
            if ($this->profiler->hasDebugger()) {
                $this->profiler->debugger()->save();
            }
        }

        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  mixed $sql
     * @return Sqlite
     */
    public function prepare(mixed $sql): Sqlite
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement = $this->connection->prepare($sql);

        if ($this->statement === false) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
            }
            $this->throwError(
                'SQLite Statement Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg()
            );
        } else if ($this->profiler !== null) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        return $this;
    }

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return Sqlite
     */
    public function bindParams(array $params): Sqlite
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParams($params);
        }

        foreach ($params as $dbColumnName => $dbColumnValue) {
            if (is_array($dbColumnValue)) {
                foreach ($dbColumnValue as $k => $dbColumnVal) {
                    ${$dbColumnName . ($k + 1)} = $dbColumnVal;
                    if ($this->statement->bindParam(':' . $dbColumnName . ($k + 1), ${$dbColumnName . ($k + 1)}) === false) {
                        $this->throwError('Error: There was an error binding the parameters');
                    }
                }
            } else {
                ${$dbColumnName} = $dbColumnValue;
                if ($this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName}) === false) {
                    $this->throwError('Error: There was an error binding the parameters');
                }
            }
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
    public function bindParam(mixed $param, mixed $value, int $type = SQLITE3_BLOB): Sqlite
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParam($param, $value);
        }

        if ($this->statement->bindParam($param, $value, $type) === false) {
            $this->throwError('Error: There was an error binding the parameter');
        }

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
    public function bindValue(mixed $param, mixed $value, int $type = SQLITE3_BLOB): Sqlite
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParam($param, $value);
        }

        if ($this->statement->bindValue($param, $value, $type) === false) {
            $this->throwError('Error: There was an error binding the value');
        }

        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Sqlite
     */
    public function execute(): Sqlite
    {
        if ($this->statement === null) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->result = $this->statement->execute();

        if ($this->result === false) {
            if ($this->profiler !== null) {
                $this->profiler->current->addError($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
            }
            $this->throwError('Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg());
        }

        if ($this->profiler !== null) {
            $this->profiler->current->finish();
            if ($this->profiler->hasDebugger()) {
                $this->profiler->debugger()->save();
            }
        }

        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @return mixed
     */
    public function fetch(): mixed
    {
        if ($this->result === null) {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return $this->result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll(): array
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
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->connection->close();
        }

        parent::disconnect();
    }

    /**
     * Escape the value
     *
     * @param  ?string $value
     * @return string
     */
    public function escape(?string $value = null): string
    {
        return $this->connection->escapeString($value);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId(): int
    {
        return $this->connection->lastInsertRowID();
    }

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows(): int
    {
        if ($this->lastSql === null) {
            return $this->connection->changes();
        } else {
            if (!($this->lastResult = $this->connection->query($this->lastSql))) {
                $this->throwError(
                    'Error: ' . $this->connection->lastErrorCode() . ' => ' . $this->connection->lastErrorMsg()
                );
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
    public function getVersion(): string
    {
        $version = $this->connection->version();
        return 'SQLite ' . $version['versionString'];
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];
        $sql    = "SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%' " .
            "UNION ALL SELECT name FROM sqlite_temp_master WHERE type IN ('table', 'view') ORDER BY 1";

        $this->query($sql);
        while (($row = $this->fetch())) {
            $tables[] = $row['name'];
        }

        return $tables;
    }

}