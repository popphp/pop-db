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
     * Transaction manager
     * @var TransactionManager|null
     */
    protected ?TransactionManager $transactionManager = null;

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
     * Return the transaction manager object, initialize on first use
     *
     * @return TransactionManager
     */
    protected function getTransactionManager(): TransactionManager
    {
        return ($this->transactionManager ??= new TransactionManager());
    }

    /**
     * Check if adapter is in the middle of an open transaction
     *
     * @return bool
     */
    public function isTransaction(): bool
    {
        return !is_null($this->transactionManager) && $this->transactionManager->isTransaction();
    }

    /**
     * Get transaction depth
     *
     * @return int
     */
    public function getTransactionDepth(): int
    {
        return is_null($this->transactionManager) ? 0 : $this->transactionManager->getTransactionDepth();
    }

    /**
     * Execute complete transaction with the DB adapter
     *
     * @param  mixed $callable
     * @param  mixed $params
     * @throws \Exception
     * @return void
     */
    public function transaction(mixed $callable, mixed $params = null): void
    {
        if (!($callable instanceof CallableObject)) {
            $callable = new CallableObject($callable, $params);
        }

        try {
            $this->beginTransaction();
            $callable->call();
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Check if transaction is success
     *
     * @return bool
     */
    abstract public function isSuccess(): bool;

    /**
     * Directly execute a SELECT SQL query or prepared statement and return the results
     *
     * @param  string|Sql $sql
     * @param  array      $params
     * @throws Exception
     * @return array
     */
    public function select(string|Sql $sql, array $params = []): array
    {
        if ((is_string($sql) && !str_starts_with(strtolower(trim($sql)), 'select')) ||
            (($sql instanceof Sql) && !($sql->hasSelect()))) {
            throw new Exception('Error: The SQL statement is not a valid SELECT statement.');
        }

        $this->executeSql($sql, $params);
        return $this->fetchAll();
    }

    /**
     * Directly execute an INSERT SQL query or prepared statement and return the results
     *
     * @param  string|Sql $sql
     * @param  array      $params
     * @throws Exception
     * @return int
     */
    public function insert(string|Sql $sql, array $params = []): int
    {
        if ((is_string($sql) && !str_starts_with(strtolower(trim($sql)), 'insert')) ||
            (($sql instanceof Sql) && !($sql->hasInsert()))) {
            throw new Exception('Error: The SQL statement is not a valid INSERT statement.');
        }

        $this->executeSql($sql, $params);
        return $this->getNumberOfAffectedRows();
    }

    /**
     * Directly execute an UPDATE SQL query or prepared statement and return the results
     *
     * @param  string|Sql $sql
     * @param  array      $params
     * @throws Exception
     * @return int
     */
    public function update(string|Sql $sql, array $params = []): int
    {
        if ((is_string($sql) && !str_starts_with(strtolower(trim($sql)), 'update')) ||
            (($sql instanceof Sql) && !($sql->hasUpdate()))) {
            throw new Exception('Error: The SQL statement is not a valid UPDATE statement.');
        }

        $this->executeSql($sql, $params);
        return $this->getNumberOfAffectedRows();
    }

    /**
     * Directly execute a DELETE SQL query or prepared statement and return the results
     *
     * @param  string|Sql $sql
     * @param  array      $params
     * @throws Exception
     * @return int
     */
    public function delete(string|Sql $sql, array $params = []): int
    {
        if ((is_string($sql) && !str_starts_with(strtolower(trim($sql)), 'delete')) ||
            (($sql instanceof Sql) && !($sql->hasDelete()))) {
            throw new Exception('Error: The SQL statement is not a valid DELETE statement.');
        }

        $this->executeSql($sql, $params);
        return $this->getNumberOfAffectedRows();
    }

    /**
     * Execute a SQL query or prepared statement with params
     *
     * @param  string|Sql $sql
     * @param  array      $params
     * @return AbstractAdapter
     */
    public function executeSql(string|Sql $sql, array $params = []): AbstractAdapter
    {
        if (!empty($params)) {
            $this->prepare($sql)
                ->bindParams($params)
                ->execute();
        } else {
            $this->query($sql);
        }

        return $this;
    }

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
     * Return the number of affected rows from the last query
     *
     * @return int
     */
    abstract public function getNumberOfAffectedRows(): int;

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