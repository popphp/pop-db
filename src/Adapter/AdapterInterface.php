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
 * Db adapter interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
interface AdapterInterface
{

    /**
     * Connect to the database
     *
     * @param  array $options
     * @return AdapterInterface
     */
    public function connect(array $options = []): AdapterInterface;

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return AdapterInterface
     */
    public function setOptions(array $options): AdapterInterface;

    /**
     * Get database connection options
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Has database connection options
     *
     * @return bool
     */
    public function hasOptions(): bool;

    /**
     * Begin a transaction
     *
     * @return AdapterInterface
     */
    public function beginTransaction(): AdapterInterface;

    /**
     * Commit a transaction
     *
     * @return AdapterInterface
     */
    public function commit(): AdapterInterface;

    /**
     * Rollback a transaction
     *
     * @return AdapterInterface
     */
    public function rollback(): AdapterInterface;

    /**
     * Check if adapter is in the middle of an open transaction
     *
     * @return bool
     */
    public function isTransaction(): bool;

    /**
     * Check is transaction is success
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return AdapterInterface
     */
    public function query(mixed $sql): AdapterInterface;

    /**
     * Prepare a SQL query.
     *
     * @param  mixed $sql
     * @return AdapterInterface
     */
    public function prepare(mixed $sql): AdapterInterface;

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return AdapterInterface
     */
    public function bindParams(array $params): AdapterInterface;

    /**
     * Execute a prepared SQL query
     *
     * @return AdapterInterface
     */
    public function execute(): AdapterInterface;

    /**
     * Fetch and return a row from the result
     *
     * @return mixed
     */
    public function fetch(): mixed;

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll(): array;

    /**
     * Create SQL builder
     *
     * @return \Pop\Db\Sql
     */
    public function createSql(): \Pop\Db\Sql;

    /**
     * Create Schema builder
     *
     * @return \Pop\Db\Sql\Schema
     */
    public function createSchema(): \Pop\Db\Sql\Schema;

    /**
     * Determine whether or not connected
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection(): mixed;

    /**
     * Determine whether or not a statement resource exists
     *
     * @return bool
     */
    public function hasStatement(): bool;

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement(): mixed;

    /**
     * Determine whether or not a result resource exists
     *
     * @return bool
     */
    public function hasResult(): bool;

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult(): mixed;

    /**
     * Add query listener to the adapter
     *
     * @param  mixed $listenerclear
     * @return mixed
     */
    public function listen(mixed $listener): mixed;

    /**
     * Set query profiler
     *
     * @param  Profiler\Profiler $profiler
     * @return AdapterInterface
     */
    public function setProfiler(Profiler\Profiler $profiler): AdapterInterface;

    /**
     * Get query profiler
     *
     * @return Profiler\Profiler|null
     */
    public function getProfiler(): Profiler\Profiler|null;

    /**
     * Clear query profiler
     *
     * @return AdapterInterface
     */
    public function clearProfiler(): AdapterInterface;

    /**
     * Determine whether or not there is an error
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Set the error
     *
     * @param  string $error
     * @return AdapterInterface
     */
    public function setError(string $error): AdapterInterface;

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError(): mixed;

    /**
     * Throw a database error exception
     *
     * @throws Exception
     * @return void
     */
    public function throwError(): void;

    /**
     * Clear the error
     *
     * @return AdapterInterface
     */
    public function clearError(): AdapterInterface;

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Escape the value
     *
     * @param  ?string $value
     * @return string
     */
    public function escape(?string $value = null): string;

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId(): int;

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows(): int;

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables(): array;

    /**
     * Return if the database has a table
     *
     * @param  string $table
     * @return bool
     */
    public function hasTable(string $table): bool;

}