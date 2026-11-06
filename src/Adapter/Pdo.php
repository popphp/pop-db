<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Adapter;

/**
 * PDO database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Pdo extends AbstractAdapter
{

    /**
     * PDO DSN
     * @var ?string
     */
    protected ?string $dsn = null;

    /**
     * PDO type
     * @var ?string
     */
    protected ?string $type = null;

    /**
     * Statement placeholder
     * @var ?string
     */
    protected ?string $placeholder = null;

    /**
     * Statement result
     * @var bool
     */
    protected bool $statementResult = false;

    /**
     * Stream cursor index, for unique cursor names (pgsql)
     * @var int
     */
    protected static int $streamIndex = 0;

    /**
     * Constructor
     *
     * Instantiate the database connection object using PDO
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
     * @return Pdo
     */
    public function connect(array $options = []): Pdo
    {
        if (!empty($options)) {
            $this->setOptions($options);
        } else if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        try {
            if ($this->type == 'sqlite') {
                $this->connection = (isset($this->options['options']) && is_array($this->options['options'])) ?
                    new \PDO($this->dsn, null, null, $this->options['options']) : new \PDO($this->dsn);
            } else {
                $this->connection = (isset($this->options['options']) && is_array($this->options['options'])) ?
                    new \PDO($this->dsn, $this->options['username'], $this->options['password'], $this->options['options']) :
                    new \PDO($this->dsn, $this->options['username'], $this->options['password']);
            }
        } catch (\PDOException $e) {
            $this->throwError('PDO Connection Error: ' . $e->getMessage() . ' (#' . $e->getCode() . ')');
        }

        return $this;
    }

    /**
     * Set database connection options
     *
     * @param  array $options
     * @return Pdo
     */
    public function setOptions(array $options): Pdo
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        $this->options = $options;

        if (!$this->hasOptions()) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $this->type = strtolower($this->options['type']);

        if ($this->type == 'sqlite') {
            if (!$this->dbFileExists()) {
                $this->throwError("Error: The database file '" . $this->options['database'] . "' does not exist.");
            }
            $this->dsn = $this->type . ':' . $this->options['database'];
        } else {
            $this->dsn = ($this->type == 'sqlsrv') ?
                $this->type . ':Server=' . $this->options['host'] . ';Database=' . $this->options['database'] :
                $this->type . ':host=' . $this->options['host'] . ';dbname=' . $this->options['database'];
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
        if (!isset($this->options['type'])) {
            return false;
        } else {
            return (strtolower($this->options['type']) == 'sqlite') ?
                (isset($this->options['database'])) :
                (isset($this->options['database']) && isset($this->options['host']) &&
                    isset($this->options['username']) && isset($this->options['password']));
        }
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
     * Return the DSN
     *
     * @return ?string
     */
    public function getDsn(): ?string
    {
        return $this->dsn;
    }

    /**
     * Return the type
     *
     * @return ?string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Begin a transaction
     *
     * @return Pdo
     */
    public function beginTransaction(): Pdo
    {
        $this->getTransactionManager()->enter(
            beginFunc: function () { $this->connection->beginTransaction(); },
            savepointFunc: function (string $sp) { $this->query('SAVEPOINT ' . $sp); },
        );

        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Pdo
     */
    public function commit(): Pdo
    {
        $this->getTransactionManager()->leave(true,
            commitFunc: function () { $this->connection->commit(); },
            rollbackFunc: function () { $this->connection->rollBack(); },
            savepointReleaseFunc: function (string $sp) { $this->query('RELEASE SAVEPOINT ' . $sp); },
        );
        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @return Pdo
     */
    public function rollback(): Pdo
    {
        $this->getTransactionManager()->leave(false,
            rollbackFunc: function () { $this->connection->rollBack(); },
            savepointRollbackFunc: function (string $sp) { $this->query('ROLLBACK TO SAVEPOINT ' . $sp); },
        );

        return $this;
    }

    /**
     * Method checks, whether the transaction is initiated.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
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
     * Method sets the value of the request attribute PDO.
     *
     * @param  int    $attribute A request attribute
     * @param  mixed  $value     The value of the attribute request
     * @return bool
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        return $this->connection->setAttribute($attribute, $value);
    }

    /**
     * The method of obtaining the value of the request attribute PDO.
     *
     * @param  int $attribute A request attribute
     * @return mixed
     */
    public function getAttribute(int $attribute): mixed
    {
        return $this->connection->getAttribute($attribute);
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Pdo
     */
    public function query(mixed $sql): Pdo
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement       = null;
        $this->statementResult = false;

        // With PDO's default exception error mode the driver throws instead of returning false
        $sth      = false;
        $executed = false;
        try {
            $sth      = $this->connection->prepare($sql);
            $executed = ($sth !== false) && $sth->execute();
        } catch (\PDOException $e) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->getErrorMessage($e->errorInfo), (string)$e->getCode());
            }
            $this->buildError((string)$e->getCode(), $e->errorInfo)
                 ->throwError();
        }

        if (!$executed) {
            $errorCode = ($sth !== false) ? $sth->errorCode() : $this->connection->errorCode();
            $errorInfo = ($sth !== false) ? $sth->errorInfo() : $this->connection->errorInfo();
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->getErrorMessage($errorInfo), $errorCode);
            }
            $this->buildError($errorCode, $errorInfo)
                 ->throwError();
        } else {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
            }
            $this->result = $sth;
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
     * @param  mixed  $sql
     * @param  ?array $attribs
     * @return Pdo
     */
    public function prepare(mixed $sql, ?array $attribs = null): Pdo
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (str_contains($sql, '?')) {
            $this->placeholder = '?';
        } else if (str_contains($sql, ':')) {
            $this->placeholder = ':';
        }

        if ($this->profiler !== null) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        if ($attribs !== null) {
            $this->statement = $this->connection->prepare($sql, $attribs);
        } else {
            $this->statement = $this->connection->prepare($sql);
        }

        return $this;
    }

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return Pdo
     */
    public function bindParams(array $params): Pdo
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParams($params);
        }

        if ($this->placeholder == '?') {
            $i = 1;
            foreach ($params as $dbColumnName => $dbColumnValue) {
                if (is_array($dbColumnValue)) {
                    foreach ($dbColumnValue as $k => $dbColumnVal) {
                        ${$dbColumnName . ($k + 1)} = $dbColumnVal;
                        $this->statement->bindParam($i, ${$dbColumnName . ($k + 1)});
                        $i++;

                    }
                } else {
                    ${$dbColumnName} = $dbColumnValue;
                    $this->statement->bindParam($i, ${$dbColumnName});
                    $i++;
                }
            }
        } else if ($this->placeholder == ':') {
            foreach ($params as $dbColumnName => $dbColumnValue) {
                if (is_array($dbColumnValue)) {
                    foreach ($dbColumnValue as $k => $dbColumnVal) {
                        ${$dbColumnName} = $dbColumnVal;
                        $this->statement->bindParam(':' . $dbColumnName . ($k + 1), ${$dbColumnName});
                    }
                } else {
                    ${$dbColumnName} = $dbColumnValue;
                    $this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName});
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
     * @param  int   $dataType
     * @param  ?int  $length
     * @param  mixed $options
     * @return Pdo
     */
    public function bindParam(mixed $param, mixed &$value, int $dataType = \PDO::PARAM_STR, ?int $length = null, mixed $options = null): Pdo
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParam($param, $value);
        }
        $this->statement->bindParam($param, $value, $dataType, (int)$length, $options);
        return $this;
    }

    /**
     * Bind a value for a prepared SQL query
     *
     * @param  mixed $param
     * @param  mixed $value
     * @param  int   $dataType
     * @return Pdo
     */
    public function bindValue(mixed $param, mixed $value, int $dataType = \PDO::PARAM_STR): Pdo
    {
        if ($this->profiler !== null) {
            $this->profiler->current->addParam($param, $value);
        }
        $this->statement->bindValue($param, $value, $dataType);
        return $this;
    }

    /**
     *  Bind a column to a PHP variable.
     *
     * @param  mixed $column    Number of the column (1-indexed) or name of the column in the result set.
     * @param  mixed $param     Name of the PHP variable to which the column will be bound.
     * @param  int   $dataType  Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @return Pdo
     */
    public function bindColumn(mixed $column, mixed $param, int $dataType = \PDO::PARAM_STR): Pdo
    {
        $this->statement->bindColumn($column, $param, $dataType);
        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Pdo
     */
    public function execute(): Pdo
    {
        if ($this->statement === null) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->statementResult = $this->statement->execute();

        if ($this->statement->errorCode() != 0) {
            if ($this->profiler !== null) {
                $this->profiler->current->addError(
                    $this->getErrorMessage($this->statement->errorInfo()), $this->statement->errorCode()
                );
            }
            $this->buildError($this->statement->errorCode(), $this->statement->errorInfo())
                 ->throwError();
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
     * @param  int $dataType  Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @return mixed
     */
    public function fetch(int $dataType = \PDO::FETCH_ASSOC): mixed
    {
        if (($this->statement !== null) && ($this->statementResult !== false)) {
            return $this->statement->fetch($dataType);
        } else {
            if ($this->result === null) {
                $this->throwError('Error: The database statement resource is not currently set.');
            }
            return $this->result->fetch($dataType);
        }
    }

    /**
     * Fetch and return all rows from the result
     *
     * @param  int $dataType  Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @return array
     */
    public function fetchAll(int $dataType = \PDO::FETCH_ASSOC): array
    {
        if (($this->statement !== null) && ($this->statementResult !== false)) {
            return $this->statement->fetchAll($dataType);
        } else {
            if ($this->result === null) {
                $this->throwError('Error: The database statement resource is not currently set.');
            }
            return $this->result->fetchAll($dataType);
        }
    }

    /**
     * Stream the rows of a query one at a time, without buffering the whole result client-side
     *
     * MySQL: buffered queries are switched off on the connection for the life of the stream (the
     * per-statement option is ignored by pdo_mysql), so the connection cannot run another command
     * until the stream has finished or been destroyed. PostgreSQL: pdo_pgsql always buffers, so a
     * server-side cursor is used; it needs a transaction, so if none is open the stream opens its
     * own and commits it when the stream ends (rolling it back only if the stream itself hits a
     * database error). Other drivers already read row by row.
     *
     * @param  mixed  $sql
     * @param  ?array $params
     * @throws Exception
     * @return \Generator
     */
    public function stream(mixed $sql, ?array $params = null): \Generator
    {
        if ($this->type == 'pgsql') {
            yield from $this->streamCursor($sql, $params);
            return;
        }

        $buffered = null;
        if ($this->type == 'mysql') {
            $buffered = $this->connection->getAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY);
            $this->connection->setAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, false);
        }

        $statement = null;

        try {
            $this->prepare($sql);
            if (!empty($params)) {
                $this->bindParams($params);
            }
            $this->execute();

            // Take the statement out of the adapter's state, so no later adapter call touches it
            $statement             = $this->statement;
            $this->statement       = null;
            $this->statementResult = false;
            $this->result          = null;

            while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
                yield $row;
            }
            if ($statement->errorCode() != 0) {
                $this->buildError($statement->errorCode(), $statement->errorInfo())->throwError();
            }
        } catch (\PDOException $e) {
            $this->buildError((string)$e->getCode(), $e->errorInfo)->throwError();
        } finally {
            $statement?->closeCursor();
            if (($buffered !== null) && $this->isConnected()) {
                $this->connection->setAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, $buffered);
            }
        }
    }

    /**
     * Stream a query's rows through a server-side cursor, STREAM_FETCH_SIZE rows per FETCH (pgsql)
     *
     * @param  mixed  $sql
     * @param  ?array $params
     * @throws Exception
     * @return \Generator
     */
    protected function streamCursor(mixed $sql, ?array $params): \Generator
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $cursor         = 'pop_db_stream_' . ++static::$streamIndex;
        $ownTransaction = !$this->isTransaction();
        $failed         = false;

        if ($ownTransaction) {
            $this->beginTransaction();
        }

        try {
            $this->prepare('DECLARE ' . $cursor . ' NO SCROLL CURSOR FOR ' . $sql);
            // Profile the caller's query, as every other stream path does, not the DECLARE wrapper
            if ($this->profiler !== null) {
                $this->profiler->current->setQuery($sql);
            }
            if (!empty($params)) {
                $this->bindParams($params);
            }
            $this->execute();
            $this->statement       = null;
            $this->statementResult = false;
            $this->result          = null;

            do {
                // query() returns false instead of throwing under PDO::ERRMODE_SILENT
                $batch = $this->connection->query('FETCH ' . static::STREAM_FETCH_SIZE . ' FROM ' . $cursor);
                if ($batch === false) {
                    $failed = true;
                    $this->buildError()->throwError();
                }
                $rows = $batch->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    yield $row;
                }
            } while (count($rows) == static::STREAM_FETCH_SIZE);
        } catch (\PDOException $e) {
            $failed = true;
            $this->buildError((string)$e->getCode(), $e->errorInfo)->throwError();
        } catch (Exception $e) {
            $failed = true;
            throw $e;
        } finally {
            // After a database error the transaction is aborted and the cursor is gone with it; a
            // closed connection has already taken both with it
            if (!$failed && $this->isConnected()) {
                $this->connection->exec('CLOSE ' . $cursor);
            }
            if ($ownTransaction && $this->isConnected()) {
                if ($failed) {
                    $this->rollback();
                } else {
                    $this->commit();
                }
            }
        }
    }

    /**
     * Escape the value
     *
     * @param  ?string $value
     * @return string
     */
    public function escape(?string $value = null): string
    {
        return substr($this->connection->quote($value), 1, -1);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId(): int
    {
        $id = 0;

        // If pgsql
        if ($this->type == 'pgsql') {
            $this->query("SELECT lastval();");
            if ($this->result !== null) {
                $insertRow = $this->result->fetch();
                $id        = $insertRow[0];
            }
        // Else, if sqlsrv
        } else if ($this->type == 'sqlsrv') {
            $this->query('SELECT SCOPE_IDENTITY() as Current_Identity');
            $row = $this->fetch();
            $id  = (isset($row['Current_Identity'])) ? $row['Current_Identity'] : 0;
        // Else, just get the last insert ID
        } else {
            $id = $this->connection->lastInsertId();
        }

        return (int)$id;
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

        if ($this->result !== null) {
            $count = $this->result->rowCount();
        } else if ($this->statement !== null) {
            $count = $this->statement->rowCount();
        } else {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $count;
    }

    /**
     * Return the number of affected rows from the last query
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfAffectedRows(): int
    {
        return $this->getNumberOfRows();
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'PDO ' . substr($this->dsn, 0, strpos($this->dsn, ':')) . ' ' .
        $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];

        if (stripos($this->dsn, 'sqlite') !== false) {
            $sql = "SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name NOT LIKE 'sqlite_%' " .
                "UNION ALL SELECT name FROM sqlite_temp_master WHERE type IN ('table', 'view') ORDER BY 1";

            $this->query($sql);
            while (($row = $this->fetch())) {
                $tables[] = $row['name'];
            }
        } else {
            if (stripos($this->dsn, 'pgsql') !== false) {
                $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
            } else if (stripos($this->dsn, 'sqlsrv') !== false) {
                $sql = "SELECT name FROM " . $this->options['database'] . ".sysobjects WHERE xtype = 'U'";
            } else {
                $sql = 'SHOW TABLES';
            }
            $this->query($sql);
            while (($row = $this->fetch())) {
                foreach($row as $value) {
                    $tables[] = $value;
                }
            }
        }

        return $tables;
    }

    /**
     * Get the error message
     *
     * @param  mixed $errorInfo
     * @return ?string
     */
    protected function getErrorMessage(mixed $errorInfo): ?string
    {
        if (is_array($errorInfo)) {
            $errorMessage = null;
            if (isset($errorInfo[1])) {
                $errorMessage .= $errorInfo[1];
            }
            if (isset($errorInfo[2])) {
                $errorMessage .= ' : ' . $errorInfo[2];
            }
        } else {
            $errorMessage = $errorInfo;
        }

        return $errorMessage;
    }

    /**
     * Build the error
     *
     * @param  ?string $code
     * @param  ?array  $info
     * @return Pdo
     */
    protected function buildError(?string $code = null, ?array $info = null): Pdo
    {
        if (($code === null) && ($info === null)) {
            $errorCode = $this->connection->errorCode();
            $errorInfo = $this->connection->errorInfo();
        } else {
            $errorCode = $code;
            $errorInfo = $info;
        }

        $this->setError('Error: ' . $errorCode . ' => ' . $this->getErrorMessage($errorInfo));
        return $this;
    }

    /**
     * Return the number of fields in the result.
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfFields(): int
    {
        $count = 0;

        if ($this->result !== null) {
            $count = $this->result->columnCount();
        } else if ($this->statement !== null) {
            $count = $this->statement->columnCount();
        } else {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $count;
    }

    /**
     * Method closes the cursor, translating the request in the ready state.
     *
     * @return bool
     */
    public function closeCursor(): bool
    {
        return $this->statement->closeCursor();
    }

    /**
     * The method returns the number of columns in the result set.
     *
     * @return int
     */
    public function getCountOfFields(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * The method receives data of one column from the next row of the result set.
     *
     * @param  ?int $num The number of the table column
     * @return mixed
     */
    public function fetchColumn(?int $num = null): mixed
    {
        return $this->statement->fetchColumn($num);
    }

    /**
     * The method returns the number of rows modified by the last SQL query.
     *
     * @return int
     */
    public function getCountOfRows(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * The method displays information about the prepared SQL command for debugging purposes.
     *
     * @param  bool $debug
     * @return string
     */
    public function debugDumpParams(bool $debug = false): bool|string
    {
        ob_start();
        $this->statement->debugDumpParams();
        $result = ob_get_contents();
        ob_end_clean();
        return (!$debug) ?: $result;
    }

    /**
     * The method runs an SQL query for execution and returns the number of rows affected during execution.
     *
     * @param  mixed $sql The SQL statement to be prepared and run
     * @return Pdo
     */
    public function exec(mixed $sql): Pdo
    {
        if (!($this->connection->exec($sql))) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $this;
    }

}
