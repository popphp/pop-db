<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Pgsql extends AbstractAdapter
{

    /**
     * Statement index
     * @var int
     */
    protected static $statementIndex = 0;

    /**
     * Connection string
     * @var string
     */
    protected $connectionString = null;

    /**
     * Prepared statement name
     * @var string
     */
    protected $statementName = null;

    /**
     * Prepared statement string
     * @var string
     */
    protected $statementString = null;

    /**
     * Prepared statement parameters
     * @var array
     */
    protected $parameters = [];

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
    public function connect(array $options = [])
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
    public function setOptions(array $options)
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
     * @return boolean
     */
    public function hasOptions()
    {
        return (isset($this->options['database']) && isset($this->options['username']) && isset($this->options['password']));
    }

    /**
     * Begin a transaction
     *
     * @return Pgsql
     */
    public function beginTransaction()
    {
        $this->query('BEGIN TRANSACTION');

        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Pgsql
     */
    public function commit()
    {
        $this->query('COMMIT');

        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @return Pgsql
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
        return $this;
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Pgsql
     */
    public function query($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (!($this->result = pg_query($this->connection, $sql))) {
            $pgError = pg_last_error($this->connection);
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($pgError);
            }
            $this->throwError($pgError);
        } else if (null !== $this->profiler) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  mixed $sql
     * @return Pgsql
     */
    public function prepare($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statementString = $sql;
        $this->statementName   = 'pop_db_adapter_pgsql_statement_' . ++static::$statementIndex;
        $this->statement       = pg_prepare($this->connection, $this->statementName, $this->statementString);

        if ($this->statement === false) {
            $pgError = pg_last_error();
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($pgError);
            }
            $this->throwError('PostgreSQL Statement Error: ' . $pgError);
        } else if (null !== $this->profiler) {
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
    public function bindParams(array $params)
    {
        if (null !== $this->profiler) {
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
    public function execute()
    {
        if ((null === $this->statement) || (null === $this->statementString) || (null === $this->statementName)) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        if (count($this->parameters) > 0)  {
            $this->result     = pg_execute($this->connection, $this->statementName, $this->parameters);
            $this->parameters = [];
        } else {
            $this->query($this->statementString);
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

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

        return pg_fetch_array($this->result, null, PGSQL_ASSOC);
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
            pg_close($this->connection);
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
        return pg_escape_string($value);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId()
    {
        $insertQuery = pg_query("SELECT lastval();");
        $insertRow   = pg_fetch_row($insertQuery);
        return $insertRow[0];
    }

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        if (null === $this->result) {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return pg_num_rows($this->result);
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = pg_version($this->connection);
        return 'PostgreSQL ' . $version['server'];
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
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