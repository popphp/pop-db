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
 * PDO database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
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
                $this->throwError("Error: The database file '" . $this->options['database'] . "'does not exists.");
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
     * @return string
     */
    public function getAttribute(int $attribute): string
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

        $sth = $this->connection->prepare($sql);

        if (!($sth->execute())) {
            if ($this->profiler !== null) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->getErrorMessage($sth->errorInfo()), $sth->errorCode());
            }
            $this->buildError($sth->errorCode(), $sth->errorInfo())
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

        if (($attribs !== null) && is_array($attribs)) {
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
        return $this->statement->fetchAll($dataType);
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

        return $id;
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
                $sql = "SELECT name FROM " . $this->database . ".sysobjects WHERE xtype = 'U'";
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
