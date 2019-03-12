<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Pdo extends AbstractAdapter
{

    /**
     * PDO DSN
     * @var string
     */
    protected $dsn = null;

    /**
     * PDO type
     * @var string
     */
    protected $type = null;

    /**
     * Statement placeholder
     * @var string
     */
    protected $placeholder = null;

    /**
     * Statement result
     * @var boolean
     */
    protected $statementResult = false;

    /**
     * Constructor
     *
     * Instantiate the database connection object using PDO
     *
     * @param  array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['type']) || !isset($options['database'])) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        try {
            $this->type = strtolower($options['type']);
            if ($this->type == 'sqlite') {
                $this->dsn = $this->type . ':' . $options['database'];
                if (isset($options['options']) && is_array($options['options'])) {
                    $this->connection = new \PDO($this->dsn, null, null, $options['options']);
                } else {
                    $this->connection = new \PDO($this->dsn);
                }
            } else {
                if (!isset($options['host']) || !isset($options['username']) || !isset($options['password'])) {
                    $this->throwError('Error: The proper database credentials were not passed.');
                }

                $this->dsn = ($this->type == 'sqlsrv') ?
                    $this->type . ':Server=' . $options['host'] . ';Database=' . $options['database'] :
                    $this->type . ':host=' . $options['host'] . ';dbname=' . $options['database'];

                if (isset($options['options']) && is_array($options['options'])) {
                    $this->connection = new \PDO($this->dsn, $options['username'], $options['password'], $options['options']);
                } else {
                    $this->connection = new \PDO($this->dsn, $options['username'], $options['password']);
                }
            }
        } catch (\PDOException $e) {
            $this->throwError('PDO Connection Error: ' . $e->getMessage() . ' (#' . $e->getCode() . ')');
        }
    }

    /**
     * Return the DSN
     *
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Return the type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Begin a transaction
     *
     * @return Pdo
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Pdo
     */
    public function commit()
    {
        $this->connection->commit();
        return $this;
    }

    /**
     * Method checks, whether the transaction is initiated.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }

    /**
     * Rollback a transaction
     *
     * @return Pdo
     */
    public function rollback()
    {
        $this->connection->rollBack();
        return $this;
    }

    /**
     * Method sets the value of the request attribute PDO.
     *
     * @param  int    $attribute A request attribute
     * @param  mixed  $value     The value of the attribute request
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return $this->connection->setAttribute($attribute, $value);
    }

    /**
     * The method of obtaining the value of the request attribute PDO.
     *
     * @param  int $attribute A request attribute
     * @return string
     */
    public function getAttribute($attribute)
    {
        return $this->connection->getAttribute($attribute);
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Pdo
     */
    public function query($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement       = null;
        $this->statementResult = false;

        $sth = $this->connection->prepare($sql);

        if (!($sth->execute())) {
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->getErrorMessage($sth->errorInfo()), $sth->errorCode());
            }
            $this->buildError($sth->errorCode(), $sth->errorInfo())
                 ->throwError();
        } else {
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
            }
            $this->result = $sth;
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  mixed  $sql
     * @param  array  $attribs
     * @return Pdo
     */
    public function prepare($sql, $attribs = null)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (strpos($sql, '?') !== false) {
            $this->placeholder = '?';
        } else if (strpos($sql, ':') !== false) {
            $this->placeholder = ':';
        }

        if (null !== $this->profiler) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        if ((null !== $attribs) && is_array($attribs)) {
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
    public function bindParams(array $params)
    {
        if (null !== $this->profiler) {
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
     * @param  int   $length
     * @param  mixed $options
     * @return Pdo
     */
    public function bindParam($param, &$value, $dataType = \PDO::PARAM_STR, $length = null, $options = null)
    {
        if (null !== $this->profiler) {
            $this->profiler->current->addParam($param, $value);
        }
        $this->statement->bindParam($param, $value, $dataType, $length, $options);
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
    public function bindValue($param, $value, $dataType = \PDO::PARAM_STR)
    {
        if (null !== $this->profiler) {
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
    public function bindColumn($column, $param, $dataType = \PDO::PARAM_STR)
    {
        $this->statement->bindColumn($column, $param, $dataType);
        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Pdo
     */
    public function execute()
    {
        if (null === $this->statement) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->statementResult = $this->statement->execute();

        if ($this->statement->errorCode() != 0) {
            if (null !== $this->profiler) {
                $this->profiler->current->addError(
                    $this->getErrorMessage($this->statement->errorInfo()), $this->statement->errorCode()
                );
            }
            $this->buildError($this->statement->errorCode(), $this->statement->errorInfo())
                 ->throwError();
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @param  int $dataType  Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @return array
     */
    public function fetch($dataType = \PDO::FETCH_ASSOC)
    {
        if ((null !== $this->statement) && ($this->statementResult !== false)) {
            return $this->statement->fetch($dataType);
        } else {
            if (null === $this->result) {
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
    public function fetchAll($dataType = \PDO::FETCH_ASSOC)
    {
        return $this->statement->fetchAll($dataType);
    }

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    public function escape($value)
    {
        return substr($this->connection->quote($value), 1, -1);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId()
    {
        $id = 0;

        // If pgsql
        if ($this->type == 'pgsql') {
            $this->query("SELECT lastval();");
            if (null !== $this->result) {
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
     * @return int
     */
    public function getNumberOfRows()
    {
        if (null === $this->result) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $this->result->rowCount();
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        return 'PDO ' . substr($this->dsn, 0, strpos($this->dsn, ':')) . ' ' .
        $this->connection->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
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
     * @return string
     */
    protected function getErrorMessage($errorInfo)
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
     * @param  string $code
     * @param  array  $info
     * @throws Exception
     * @return Pdo
     */
    protected function buildError($code = null, $info = null)
    {
        if ((null === $code) && (null === $info)) {
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
    public function getNumberOfFields()
    {
        if (!isset($this->result)) {
            throw new Exception('Error: The database result resource is not currently set.');
        }

        return $this->result->columnCount();
    }

    /**
     * Method closes the cursor, translating the request in the ready state.
     *
     * @return bool
     */
    public function closeCursor()
    {
        return $this->statement->closeCursor();
    }

    /**
     * The method returns the number of columns in the result set.
     *
     * @return int
     */
    public function getCountOfFields()
    {
        return $this->statement->columnCount();
    }

    /**
     * The method receives data of one column from the next row of the result set.
     *
     * @param  int $num The number of the table column
     * @return mixed
     */
    public function fetchColumn($num = null)
    {
        return $this->statement->fetchColumn($num);
    }

    /**
     * The method returns the number of rows modified by the last SQL query.
     *
     * @return int
     */
    public function getCountOfRows()
    {
        return $this->statement->rowCount();
    }

    /**
     * The method displays information about the prepared SQL command for debugging purposes.
     *
     * @param  boolean $debug
     * @return string
     */
    public function debugDumpParams($debug = false)
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
     * @param  string $sql The SQL statement to be prepared and run
     * @return Pdo
     */
    public function exec($sql)
    {
        if (!($this->connection->exec($sql))) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $this;
    }

}