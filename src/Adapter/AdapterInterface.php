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
 * Db adapter interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
interface AdapterInterface
{

    /**
     * Connect to the database
     *
     * @param  array $options
     * @return AdapterInterface
     */
    public function connect(array $options = []);

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return AdapterInterface
     */
    public function setOptions(array $options);

    /**
     * Get database connection options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Has database connection options
     *
     * @return boolean
     */
    public function hasOptions();

    /**
     * Begin a transaction
     *
     * @return AdapterInterface
     */
    public function beginTransaction();

    /**
     * Commit a transaction
     *
     * @return AdapterInterface
     */
    public function commit();

    /**
     * Rollback a transaction
     *
     * @return AdapterInterface
     */
    public function rollback();

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return AdapterInterface
     */
    public function query($sql);

    /**
     * Prepare a SQL query.
     *
     * @param  string $sql
     * @return AdapterInterface
     */
    public function prepare($sql);

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return AdapterInterface
     */
    public function bindParams(array $params);

    /**
     * Execute a prepared SQL query
     *
     * @return AdapterInterface
     */
    public function execute();

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    public function fetch();

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll();

    /**
     * Create SQL builder
     *
     * @return \Pop\Db\Sql
     */
    public function createSql();

    /**
     * Create Schema builder
     *
     * @return \Pop\Db\Sql\Schema
     */
    public function createSchema();

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection();

    /**
     * Determine whether or not a statement resource exists
     *
     * @return boolean
     */
    public function hasStatement();

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement();

    /**
     * Determine whether or not a result resource exists
     *
     * @return boolean
     */
    public function hasResult();

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Add query listener to the adapter
     *
     * @param  mixed $listener
     * @return AdapterInterface
     */
    public function listen($listener);

    /**
     * Set query profiler
     *
     * @param  Profiler\Profiler $profiler
     * @return AdapterInterface
     */
    public function setProfiler(Profiler\Profiler $profiler);

    /**
     * Get query profiler
     *
     * @return Profiler\Profiler
     */
    public function getProfiler();

    /**
     * Clear query profiler
     *
     * @return AdapterInterface
     */
    public function clearProfiler();

    /**
     * Determine whether or not there is an error
     *
     * @return boolean
     */
    public function hasError();

    /**
     * Set the error
     *
     * @param  string $error
     * @return AdapterInterface
     */
    public function setError($error);

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError();

    /**
     * Throw a database error exception
     *
     * @throws Exception
     * @return void
     */
    public function throwError();

    /**
     * Clear the error
     *
     * @return AdapterInterface
     */
    public function clearError();

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect();

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    public function escape($value);

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId();

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows();

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables();

    /**
     * Return if the database has a table
     *
     * @param  string  $table
     * @return boolean
     */
    public function hasTable($table);

}