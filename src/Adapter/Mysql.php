<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter;

/**
 * MySQL database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class Mysql extends AbstractAdapter
{

    /**
     * Statement result
     * @var bool
     */
    protected bool $statementResult = false;

    /**
     * Constructor
     *
     * Instantiate the MySQL database connection object using mysqli
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
     * @return Mysql
     */
    public function connect(array $options = []): Mysql
    {
        if (!empty($options)) {
            $this->setOptions($options);
        } else if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $this->connection = new \mysqli(
            $this->options['host'],     $this->options['username'], $this->options['password'],
            $this->options['database'], $this->options['port'],     $this->options['socket']
        );

        if ($this->connection->connect_error != '') {
            $this->throwError(
                'MySQL Connection Error: ' . $this->connection->connect_error .
                ' (#' . $this->connection->connect_errno . ')'
            );
        }

        return $this;
    }

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return Mysql
     */
    public function setOptions(array $options): Mysql
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }
        if (!isset($options['port'])) {
            $options['port'] = ini_get('mysqli.default_port');
        }
        if (!isset($options['socket'])) {
            $options['socket'] = ini_get('mysqli.default_socket');
        }

        $this->options = $options;

        if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
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
     * @param  ?int    $flags
     * @param  ?string $name
     * @return Mysql
     */
    public function beginTransaction(?int $flags = null, ?string $name = null): Mysql
    {
        $this->getTransactionManager()->enter(
            beginFunc: function () use ($flags, $name) {
                if (($flags !== null) && ($name !== null)) {
                    $this->connection->begin_transaction($flags, $name);
                } else if ($flags !== null) {
                    $this->connection->begin_transaction($flags);
                } else {
                    $this->connection->begin_transaction();
                }
            },
            savepointFunc: function (string $sp) { $this->connection->savepoint($sp); },
        );

        return $this;
    }

    /**
     * Commit a transaction
     *
     * @param  ?int    $flags
     * @param  ?string $name
     * @return Mysql
     */
    public function commit(?int $flags = null, ?string $name = null): Mysql
    {
        $this->getTransactionManager()->leave(true,
            commitFunc: function () use ($flags, $name) {
                if (($flags !== null) && ($name !== null)) {
                    $this->connection->commit($flags, $name);
                } else if ($flags !== null) {
                    $this->connection->commit($flags);
                } else {
                    $this->connection->commit();
                }
            },
            savepointReleaseFunc: function (string $sp) { $this->connection->release_savepoint($sp); },
        );

        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @param  ?int    $flags
     * @param  ?string $name
     * @return Mysql
     */
    public function rollback(?int $flags = null, ?string $name = null): Mysql
    {
        $this->getTransactionManager()->leave(false,
            rollbackFunc: function () use ($flags, $name) {
                if (($flags !== null) && ($name !== null)) {
                    $this->connection->rollback($flags, $name);
                } else if ($flags !== null) {
                    $this->connection->rollback($flags);
                } else {
                    $this->connection->rollback();
                }
            },
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
        return ((($this->result) || ($this->statementResult)) && (!$this->hasError()));
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Mysql
     */
    public function query(mixed $sql): Mysql
    {
        $this->statement       = null;
        $this->statementResult = false;

        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (!($this->result = $this->connection->query($sql))) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->connection->error, $this->connection->errno);
            }
            $this->throwError('Error: ' . $this->connection->errno . ' => ' . $this->connection->error);
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
     * @return Mysql
     */
    public function prepare(mixed $sql): Mysql
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement = $this->connection->stmt_init();
        if (!$this->statement->prepare($sql)) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->statement->error, $this->statement->errno);
            }
            $this->throwError('MySQL Statement Error: ' . $this->statement->errno . ' (#' . $this->statement->error . ')');
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
     * @return Mysql
     */
    public function bindParams(array $params): Mysql
    {
        $bindParams = [''];

        if ($this->profiler !== null) {
            $this->profiler->current->addParams($params);
        }

        $i = 1;
        foreach ($params as $dbColumnName => $dbColumnValue) {
            if (is_array($dbColumnValue)) {
                foreach ($dbColumnValue as $dbColumnVal) {
                    ${$dbColumnName . $i} = $dbColumnVal;

                    if (is_int($dbColumnVal)) {
                        $bindParams[0] .= 'i';
                    } else if (is_double($dbColumnVal)) {
                        $bindParams[0] .= 'd';
                    } else if (is_string($dbColumnVal)) {
                        $bindParams[0] .= 's';
                    } else if (is_null($dbColumnVal)) {
                        $bindParams[0] .= 's';
                    } else {
                        $bindParams[0] .= 'b';
                    }

                    $bindParams[] = &${$dbColumnName . $i};
                    $i++;
                }
            } else {
                ${$dbColumnName . $i} = $dbColumnValue;

                if (is_int($dbColumnValue)) {
                    $bindParams[0] .= 'i';
                } else if (is_double($dbColumnValue)) {
                    $bindParams[0] .= 'd';
                } else if (is_string($dbColumnValue)) {
                    $bindParams[0] .= 's';
                } else if (is_null($dbColumnValue)) {
                    $bindParams[0] .= 's';
                } else {
                    $bindParams[0] .= 'b';
                }

                $bindParams[] = &${$dbColumnName . $i};
                $i++;
            }

        }

        if (call_user_func_array([$this->statement, 'bind_param'], $bindParams) === false) {
            $this->throwError('Error: There was an error binding the parameters');
        }

        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @throws Exception
     * @return Mysql
     */
    public function execute(): Mysql
    {
        if ($this->statement === null) {
            $this->throwError('Error: The database statement resource is not currently set');
        }

        $this->statementResult = $this->statement->execute();

        if (!empty($this->statement->error)) {
            if ($this->profiler !== null) {
                $this->profiler->current->addError($this->statement->error, $this->statement->errno);
            }
            $this->throwError('MySQL Statement Error: ' . $this->statement->errno . ' (#' . $this->statement->error . ')');
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
     * @throws Exception
     * @return mixed
     */
    public function fetch(): mixed
    {
        if (($this->statement !== null) && ($this->statementResult !== false)) {
            $params     = [];
            $bindParams = [];
            $row        = false;

            $metaData = $this->statement->result_metadata();
            if ($metaData !== false) {
                foreach ($metaData->fetch_fields() as $col) {
                    ${$col->name} = null;
                    $bindParams[] = &${$col->name};
                    $params[]     = $col->name;
                }

                call_user_func_array([$this->statement, 'bind_result'], $bindParams);

                if (($r = $this->statement->fetch()) != false) {
                    $row = [];
                    foreach ($bindParams as $dbColumnName => $dbColumnValue) {
                        $row[$params[$dbColumnName]] = $dbColumnValue;
                    }
                }
            }

            return $row;
        } else {
            if ($this->result === null) {
                $this->throwError('Error: The database result resource is not currently set.');
            }
            return $this->result->fetch_array(MYSQLI_ASSOC);
        }
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
        return (!empty($value)) ? $this->connection->real_escape_string($value) : '';
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId(): int
    {
        return $this->connection->insert_id;
    }

    /**
     * Return the number of rows from the last query
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfRows(): int
    {
        $count = 0;

        if ($this->statement !== null) {
            $this->statement->store_result();
            $count = $this->statement->num_rows;
        } else if ($this->result !== null) {
            $count = $this->result->num_rows;
        } else {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return $count;
    }

    /**
     * Return the number of affected rows from the last query
     *
     * @return int
     */
    public function getNumberOfAffectedRows(): int
    {
        return $this->connection->affected_rows;
    }

    /**
     * Return the database version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'MySQL ' . $this->connection->server_info;
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];

        $this->query('SHOW TABLES');
        while (($row = $this->fetch())) {
            foreach($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

}
