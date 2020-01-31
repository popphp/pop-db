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

use Pop\Db\Sql;
use Pop\Utils\CallableObject;

/**
 * Db abstract adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractAdapter implements AdapterInterface
{

    /**
     * Database connection options
     * @var mixed
     */
    protected $options = [];

    /**
     * Database connection object/resource
     * @var mixed
     */
    protected $connection = null;

    /**
     * Statement object/resource
     * @var mixed
     */
    protected $statement = null;

    /**
     * Result object/resource
     * @var mixed
     */
    protected $result = null;

    /**
     * Error string/object/resource
     * @var mixed
     */
    protected $error = null;

    /**
     * Query listener object/resource
     * @var CallableObject
     */
    protected $listener = null;

    /**
     * Query profiler
     * @var Profiler\Profiler
     */
    protected $profiler = null;

    /**
     * Constructor
     *
     * Instantiate the database adapter object
     *
     * @param  array $options
     */
    abstract public function __construct(array $options = []);

    /**
     * Connect to the database
     *
     * @param  array $options
     * @return AbstractAdapter
     */
    abstract public function connect(array $options = []);

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return AdapterInterface
     */
    abstract public function setOptions(array $options);

    /**
     * Get database connection options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Has database connection options
     *
     * @return boolean
     */
    abstract public function hasOptions();

    /**
     * Begin a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function beginTransaction();

    /**
     * Commit a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function commit();

    /**
     * Rollback a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function rollback();

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return AbstractAdapter
     */
    abstract public function query($sql);

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @return AbstractAdapter
     */
    abstract public function prepare($sql);

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return AbstractAdapter
     */
    abstract public function bindParams(array $params);

    /**
     * Execute a prepared SQL query
     *
     * @return AbstractAdapter
     */
    abstract public function execute();

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    abstract public function fetch();

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    abstract public function fetchAll();

    /**
     * Create SQL builder
     *
     * @return Sql
     */
    public function createSql()
    {
        return new Sql($this);
    }

    /**
     * Create Schema builder
     *
     * @return Sql\Schema
     */
    public function createSchema()
    {
        return new Sql\Schema($this);
    }

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return (null !== $this->connection);
    }

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Determine whether or not a statement resource exists
     *
     * @return boolean
     */
    public function hasStatement()
    {
        return (null !== $this->statement);
    }

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Determine whether or not a result resource exists
     *
     * @return boolean
     */
    public function hasResult()
    {
        return (null !== $this->result);
    }

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Add query listener to the adapter
     *
     * @param  mixed             $listener
     * @param  mixed             $params
     * @param  Profiler\Profiler $profiler
     * @return mixed
     */
    public function listen($listener, $params = null, Profiler\Profiler $profiler = null)
    {
        if (null !== $profiler) {
            $this->profiler = $profiler;
        }
        if (null === $this->profiler) {
            $this->profiler = new Profiler\Profiler();
        }

        if (!($listener instanceof CallableObject)) {
            $this->listener = new CallableObject($listener, [$this->profiler]);
            if (null !== $params) {
                if (is_array($params)) {
                    $this->listener->addParameters($params);
                } else {
                    $this->listener->addParameter($params);
                }
            }
        } else {
            $this->listener = $listener;
            if (null !== $params) {
                if (is_array($params)) {
                    array_unshift($params, $this->profiler);
                } else {
                    $params = [$this->profiler, $params];
                }
                $this->listener->addParameters($params);
            } else {
                $this->listener->addNamedParameter('profiler', $this->profiler);
            }
        }

        return $this->listener->call();
    }

    /**
     * Get query listener
     *
     * @return CallableObject
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Set query profiler
     *
     * @param  Profiler\Profiler $profiler
     * @return AbstractAdapter
     */
    public function setProfiler(Profiler\Profiler $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * Get query profiler
     *
     * @return Profiler\Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * Clear query profiler
     *
     * @return AbstractAdapter
     */
    public function clearProfiler()
    {
        unset($this->profiler);
        $this->profiler = null;
        return $this;
    }

    /**
     * Determine whether or not there is an error
     *
     * @return boolean
     */
    public function hasError()
    {
        return (null !== $this->error);
    }

    /**
     * Set the error
     *
     * @param  string $error
     * @return AbstractAdapter
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Throw a database error exception
     *
     * @param  string $error
     * @throws Exception
     * @return void
     */
    public function throwError($error = null)
    {
        if (null !== $error) {
            $this->setError($error);
        }
        if (null !== $this->error) {
            throw new Exception($this->error);
        }
    }

    /**
     * Clear the error
     *
     * @return AbstractAdapter
     */
    public function clearError()
    {
        $this->error = null;
        return $this;
    }

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect()
    {
        unset($this->connection);
        unset($this->statement);
        unset($this->result);
        unset($this->error);

        $this->connection = null;
        $this->result     = null;
        $this->statement  = null;
        $this->error      = null;
    }

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    abstract public function escape($value);

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    abstract public function getLastId();

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    abstract public function getNumberOfRows();

    /**
     * Return the database version
     *
     * @return string
     */
    abstract public function getVersion();

    /**
     * Return the tables in the database
     *
     * @return array
     */
    abstract public function getTables();

    /**
     * Return if the database has a table
     *
     * @param  string  $table
     * @return boolean
     */
    public function hasTable($table)
    {
        return (in_array($table, $this->getTables()));
    }

}