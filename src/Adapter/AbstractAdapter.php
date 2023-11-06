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

use Pop\Db\Sql;
use Pop\Utils\CallableObject;

/**
 * Db abstract adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
abstract class AbstractAdapter implements AdapterInterface
{

    /**
     * Database connection options
     * @var array
     */
    protected array $options = [];

    /**
     * Database connection object/resource
     * @var mixed
     */
    protected mixed $connection = null;

    /**
     * Statement object/resource
     * @var mixed
     */
    protected mixed $statement = null;

    /**
     * Result object/resource
     * @var mixed
     */
    protected mixed $result = null;

    /**
     * Error string/object/resource
     * @var mixed
     */
    protected mixed $error = null;

    /**
     * Query listener object/resource
     * @var mixed
     */
    protected mixed $listener = null;

    /**
     * Query profiler
     * @var ?Profiler\Profiler
     */
    protected ?Profiler\Profiler $profiler = null;

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
    abstract public function connect(array $options = []): AbstractAdapter;

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return AbstractAdapter
     */
    abstract public function setOptions(array $options): AbstractAdapter;

    /**
     * Get database connection options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Has database connection options
     *
     * @return bool
     */
    abstract public function hasOptions(): bool;

    /**
     * Begin a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function beginTransaction(): AbstractAdapter;

    /**
     * Commit a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function commit(): AbstractAdapter;

    /**
     * Rollback a transaction
     *
     * @return AbstractAdapter
     */
    abstract public function rollback(): AbstractAdapter;

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return AbstractAdapter
     */
    abstract public function query(mixed $sql): AbstractAdapter;

    /**
     * Prepare a SQL query
     *
     * @param  mixed $sql
     * @return AbstractAdapter
     */
    abstract public function prepare(mixed $sql): AbstractAdapter;

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return AbstractAdapter
     */
    abstract public function bindParams(array $params): AbstractAdapter;

    /**
     * Execute a prepared SQL query
     *
     * @return AbstractAdapter
     */
    abstract public function execute(): AbstractAdapter;

    /**
     * Fetch and return a row from the result
     *
     * @return mixed
     */
    abstract public function fetch(): mixed;

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    abstract public function fetchAll(): array;

    /**
     * Create SQL builder
     *
     * @return Sql
     */
    public function createSql(): Sql
    {
        return new Sql($this);
    }

    /**
     * Create Schema builder
     *
     * @return Sql\Schema
     */
    public function createSchema(): Sql\Schema
    {
        return new Sql\Schema($this);
    }

    /**
     * Determine whether or not connected
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return ($this->connection !== null);
    }

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection(): mixed
    {
        return $this->connection;
    }

    /**
     * Determine whether or not a statement resource exists
     *
     * @return bool
     */
    public function hasStatement(): bool
    {
        return ($this->statement !== null);
    }

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return $this->statement;
    }

    /**
     * Determine whether or not a result resource exists
     *
     * @return bool
     */
    public function hasResult(): bool
    {
        return ($this->result !== null);
    }

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult(): mixed
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
    public function listen(mixed $listener, mixed $params = null, Profiler\Profiler $profiler = new Profiler\Profiler()): mixed
    {
        $this->profiler = $profiler;

        if (!($listener instanceof CallableObject)) {
            $this->listener = new CallableObject($listener, [$this->profiler]);
            if ($params !== null) {
                if (is_array($params)) {
                    $this->listener->addParameters($params);
                } else {
                    $this->listener->addParameter($params);
                }
            }
        } else {
            $this->listener = $listener;
            if ($params !== null) {
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

        $handler = $this->listener->call();
        if ($this->profiler->hasDebugger()) {
            $this->profiler->getDebugger()->addHandler($handler);
        }

        return $handler;
    }

    /**
     * Get query listener
     *
     * @return mixed
     */
    public function getListener(): mixed
    {
        return $this->listener;
    }

    /**
     * Set query profiler
     *
     * @param  Profiler\Profiler $profiler
     * @return AbstractAdapter
     */
    public function setProfiler(Profiler\Profiler $profiler): AbstractAdapter
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * Get query profiler
     *
     * @return Profiler\Profiler|null
     */
    public function getProfiler(): Profiler\Profiler|null
    {
        return $this->profiler;
    }

    /**
     * Clear query profiler
     *
     * @return AbstractAdapter
     */
    public function clearProfiler(): AbstractAdapter
    {
        unset($this->profiler);
        $this->profiler = null;
        return $this;
    }

    /**
     * Determine whether or not there is an error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return ($this->error !== null);
    }

    /**
     * Set the error
     *
     * @param  string $error
     * @return AbstractAdapter
     */
    public function setError(string $error): AbstractAdapter
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError(): mixed
    {
        return $this->error;
    }

    /**
     * Throw a database error exception
     *
     * @param  ?string $error
     * @throws Exception
     * @return void
     */
    public function throwError(?string $error = null): void
    {
        if ($error !== null) {
            $this->setError($error);
        }
        if ($this->error !== null) {
            throw new Exception($this->error);
        }
    }

    /**
     * Clear the error
     *
     * @return AbstractAdapter
     */
    public function clearError(): AbstractAdapter
    {
        $this->error = null;
        return $this;
    }

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect(): void
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
     * @param  ?string $value
     * @return string
     */
    abstract public function escape(?string $value = null): string;

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    abstract public function getLastId(): int;

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    abstract public function getNumberOfRows(): int;

    /**
     * Return the database version
     *
     * @return string
     */
    abstract public function getVersion(): string;

    /**
     * Return the tables in the database
     *
     * @return array
     */
    abstract public function getTables(): array;

    /**
     * Return if the database has a table
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        return (in_array($table, $this->getTables()));
    }

}