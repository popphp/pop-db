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
 * PostgreSQL database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Pgsql extends AbstractAdapter
{

    /**
     * Statement index
     * @var int
     */
    protected static $statementIndex = 0;

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
     * @throws Exception
     * @return Pgsql
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
        }

        $connectionString = "host=" . $options['host'] . " dbname=" . $options['database'] .
            " user=" . $options['username'] . " password=" . $options['password'];

        if (isset($options['port'])) {
            $connectionString .= " port=" . $options['port'];
        }
        if (isset($options['hostaddr'])) {
            $connectionString .= " hostaddr=" . $options['hostaddr'];
        }
        if (isset($options['connect_timeout'])) {
            $connectionString .= " connect_timeout=" . $options['connect_timeout'];
        }
        if (isset($options['options'])) {
            $connectionString .= " options=" . $options['options'];
        }
        if (isset($options['sslmode'])) {
            $connectionString .= " sslmode=" . $options['sslmode'];
        }

        $pg_connect = (isset($options['persist']) && ($options['persist'])) ? 'pg_pconnect' : 'pg_connect';

        if (isset($options['type'])) {
            $this->connection = $pg_connect($connectionString, $options['type']);
        } else {
            $this->connection = $pg_connect($connectionString);
        }

        if (!$this->connection) {
            $this->setError('PostgreSQL Connection Error: Unable to connect to the database.')
                 ->throwError();
        }
    }

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return Pgsql
     */
    public function query($sql)
    {
        if (!($this->result = pg_query($this->connection, $sql))) {
            $this->setError(pg_last_error($this->connection))
                 ->throwError();
        }
        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @return Pgsql
     */
    public function prepare($sql)
    {
        $this->statementString = $sql;
        $this->statementName   = 'pop_db_adapter_pgsql_statement_' . ++static::$statementIndex;
        $this->statement       = pg_prepare($this->connection, $this->statementName, $this->statementString);
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
            $this->setError('Error: The database statement resource is not currently set.')
                 ->throwError();
        }

        if (count($this->parameters) > 0)  {
            $this->result     = pg_execute($this->connection, $this->statementName, $this->parameters);
            $this->parameters = [];
        } else {
            $this->query($this->statementString);
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
        if (!isset($this->result)) {
            $this->setError('Error: The database result resource is not currently set.')
                 ->throwError();
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
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        if (!isset($this->result)) {
            $this->setError('Error: The database result resource is not currently set.')
                 ->throwError();
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