<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter;

/**
 * PostgreSQL database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
class Pgsql extends AbstractAdapter
{

    /**
     * Statement index
     * @var int
     */
    protected static int $statementIndex = 0;

    /**
     * Connection string
     * @var ?string
     */
    protected ?string $connectionString = null;

    /**
     * Prepared statement name
     * @var ?string
     */
    protected ?string $statementName = null;

    /**
     * Prepared statement string
     * @var string
     */
    protected ?string $statementString = null;

    /**
     * Prepared statement parameters
     * @var array
     */
    protected array $parameters = [];

    /**
     * Constructor
     *
     * Instantiate the PostgreSQL database connection object
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
     * @return Pgsql
     */
    public function connect(array $options = []): Pgsql
    {
        if (!empty($options)) {
            $this->setOptions($options);
        } else if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $pg_connect = (isset($this->options['persist']) && ($this->options['persist'])) ? 'pg_pconnect' : 'pg_connect';

        $this->connection = (isset($this->options['type'])) ?
            $pg_connect($this->connectionString, $this->options['type']) : $pg_connect($this->connectionString);

        if (!$this->connection) {
            $this->throwError('PostgreSQL Connection Error: Unable to connect to the database.');
        }

        return $this;
    }

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return Pgsql
     */
    public function setOptions(array $options): Pgsql
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        $this->options = $options;

        if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $this->connectionString = "host=" . $this->options['host'] . " dbname=" . $this->options['database'] .
            " user=" . $this->options['username'] . " password=" . $this->options['password'];

        if (isset($this->options['port'])) {
            $this->connectionString .= " port=" . $this->options['port'];
        }
        if (isset($this->options['hostaddr'])) {
            $this->connectionString .= " hostaddr=" . $this->options['hostaddr'];
        }
        if (isset($this->options['connect_timeout'])) {
            $this->connectionString .= " connect_timeout=" . $this->options['connect_timeout'];
        }
        if (isset($this->options['options'])) {
            $this->connectionString .= " options=" . $this->options['options'];
        }
        if (isset($this->options['sslmode'])) {
            $this->connectionString .= " sslmode=" . $this->options['sslmode'];
        }

        return $this;
    }

    /**
     * Has database connection options
     *
     * @return bool
     */
    public function hasOptions(): bool
    {
        return (isset($this->options['database']) && isset($this->options['username']) && isset($this->options['password']));
    }

    /**
     * Begin a transaction
     *
     * @return Pgsql
     */
    public function beginTransaction(): Pgsql
    {
        $this->getTransactionManager()->enter(
            beginFunc: function () { $this->query('BEGIN TRANSACTION'); },
            savepointFunc: function (string $sp) { $this->query('SAVEPOINT ' . $sp); },
        );

        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Pgsql
     */
    public function commit(): Pgsql
    {
        $this->getTransactionManager()->leave(true,
            commitFunc: function () { $this->query('COMMIT'); },
            savepointReleaseFunc: function (string $sp) { $this->query('RELEASE SAVEPOINT ' . $sp); },
        );

        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @return Pgsql
     */
    public function rollback(): Pgsql
    {
        $this->getTransactionManager()->leave(false,
            rollbackFunc: function () { $this->query('ROLLBACK'); },
            savepointRollbackFunc: function (string $sp) { $this->query('ROLLBACK TO SAVEPOINT ' . $sp); },
        );

        return $this;
    }

    /**
     * Check if transaction is success
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return ((($this->result !== null) && ($this->result !== false)) && (!$this->hasError()));
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Pgsql
     */
    public function query(mixed $sql): Pgsql
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (!($this->result = pg_query($this->connection, $sql))) {
            $pgError = pg_last_error($this->connection);
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($pgError);
            }
            $this->throwError($pgError);
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
     * @return Pgsql
     */
    public function prepare(mixed $sql): Pgsql
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statementString = $sql;
        $this->statementName   = 'pop_db_adapter_pgsql_statement_' . ++static::$statementIndex;
        $this->statement       = pg_prepare($this->connection, $this->statementName, $this->statementString);

        if ($this->statement === false) {
            $pgError = pg_last_error();
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($pgError);
            }
            $this->throwError('PostgreSQL Statement Error: ' . $pgError);
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
     * @return Pgsql
     */
    public function bindParams(array $params): Pgsql
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParams($params);
        }

        $this->parameters = [];

        foreach ($params as $param) {
            $this->parameters[] = $param;
        }

        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Pgsql
     */
    public function execute(): Pgsql
    {
        if (($this->statement === null) || ($this->statementString === null) || ($this->statementName === null)) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        if (count($this->parameters) > 0)  {
            $this->result     = pg_execute($this->connection, $this->statementName, $this->parameters);
            $this->parameters = [];
        } else {
            $this->query($this->statementString);
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

        return pg_fetch_array($this->result, null, PGSQL_ASSOC);
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
            pg_close($this->connection);
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
        return (!empty($value)) ? pg_escape_string($this->connection, $value) : '';
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId(): int
    {
        $insertQuery = pg_query($this->connection, "SELECT lastval();");
        $insertRow   = pg_fetch_row($insertQuery);
        return $insertRow[0];
    }

    /**
     * Return the number of rows from the last query
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfRows(): int
    {
        if ($this->result === null) {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return pg_num_rows($this->result);
    }

    /**
     * Return the number of affected rows from the last query
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfAffectedRows(): int
    {
        $count = 0;

        if ($this->statement !== null) {
            $count = pg_affected_rows($this->statement);
        } else if ($this->result !== null) {
            $count = pg_affected_rows($this->result);
        } else {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return $count;
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion(): string
    {
        $version = pg_version($this->connection);
        return 'PostgreSQL ' . $version['server'];
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];

        $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        while (($row = $this->fetch())) {
            foreach($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

}
