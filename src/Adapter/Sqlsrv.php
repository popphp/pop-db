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
 * SQL Server database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Sqlsrv extends AbstractAdapter
{

    /**
     * Database
     * @var string
     */
    protected $database = null;

    /**
     * Prepared statement string
     * @var string
     */
    protected $statementString = null;

    /**
     * Statement result
     * @var boolean
     */
    protected $statementResult = false;

    /**
     * Constructor
     *
     * Instantiate the SQL Server database connection object
     *
     * @param  array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $info = [
            'Database' => $options['database'],
            'UID'      => $options['username'],
            'PWD'      => $options['password']
        ];

        if (isset($options['info']) && is_array($options['info'])) {
            $info = array_merge($info, $options['info']);
        }

        if (!isset($info['ReturnDatesAsStrings'])) {
            $info['ReturnDatesAsStrings'] = true;
        }

        $this->connection = sqlsrv_connect($options['host'], $info);
        $this->database   = $options['database'];

        if ($this->connection == false) {
            $this->throwError('SQL Server Connection Error: ' . $this->getSqlSrvErrors());
        }
    }

    /**
     * Begin a transaction
     *
     * @return Sqlsrv
     */
    public function beginTransaction()
    {
        sqlsrv_begin_transaction($this->connection);
        return $this;
    }

    /**
     * Commit a transaction
     *
     * @return Sqlsrv
     */
    public function commit()
    {
        sqlsrv_commit($this->connection);
        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @return Sqlsrv
     */
    public function rollback()
    {
        sqlsrv_rollback($this->connection);
        return $this;
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Sqlsrv
     */
    public function query($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement       = null;
        $this->statementResult = false;

        if (!($this->result = sqlsrv_query($this->connection, $sql))) {
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $errors = $this->getSqlSrvErrors(false);
                foreach ($errors as $code => $error) {
                    $this->profiler->current->addError($error, $code);
                }
            }
            $this->throwError('Error: ' . $this->getSqlSrvErrors());
        } else if (null !== $this->profiler) {
            $this->profiler->addStep();
            $this->profiler->current->setQuery($sql);
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  mixed $sql
     * @return Sqlsrv
     */
    public function prepare($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statementString = $sql;

        if (strpos($this->statementString, '?') === false) {
            $this->statement = sqlsrv_prepare($this->connection, $this->statementString);
            if ($this->statement === false) {
                if (null !== $this->profiler) {
                    $this->profiler->addStep();
                    $this->profiler->current->setQuery($sql);
                    $errors = $this->getSqlSrvErrors(false);
                    foreach ($errors as $code => $error) {
                        $this->profiler->current->addError($error, $code);
                    }
                }
                $this->throwError('SQL Server Statement Error: ' . $this->getSqlSrvErrors());
            } else if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
            }
        }

        return $this;
    }

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @param  mixed $options
     * @return Sqlsrv
     */
    public function bindParams(array $params, $options = null)
    {
        if (null !== $this->profiler) {
            $this->profiler->current->addParams($params);
        }

        $bindParams = [];

        $i = 1;
        foreach ($params as $dbColumnName => $dbColumnValue) {
            if (is_array($dbColumnValue)) {
                foreach ($dbColumnValue as $k => $dbColumnVal) {
                    ${$dbColumnName . $i} = $dbColumnVal;
                    $bindParams[] = &${$dbColumnName . $i};
                    $i++;
                }
            } else {
                ${$dbColumnName . $i} = $dbColumnValue;
                $bindParams[] = &${$dbColumnName . $i};
                $i++;
            }
        }

        if (count($bindParams) > 0) {
            $this->statement = (null !== $options) ?
                sqlsrv_prepare($this->connection, $this->statementString, $bindParams, $options) :
                sqlsrv_prepare($this->connection, $this->statementString, $bindParams);

            if ($this->statement === false) {
                $this->throwError('Error: ' . $this->getSqlSrvErrors());
            }
        }

        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Sqlsrv
     */
    public function execute()
    {
        if (null === $this->statement) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->statementResult = sqlsrv_execute($this->statement);

        if ($this->statementResult === false) {
            if (null !== $this->profiler) {
                $errors = $this->getSqlSrvErrors(false);
                foreach ($errors as $code => $error) {
                    $this->profiler->current->addError($error, $code);
                }
            }
            $this->throwError('Error: ' . $this->getSqlSrvErrors());
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
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
        if ((null !== $this->statement) && ($this->statementResult !== false)) {
            return sqlsrv_fetch_array($this->statement, SQLSRV_FETCH_ASSOC);
        } else {
            if (null === $this->result) {
                $this->throwError('Error: The database result resource is not currently set.');
            }

            return sqlsrv_fetch_array($this->result, SQLSRV_FETCH_ASSOC);
        }
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
            sqlsrv_close($this->connection);
        }

        parent::disconnect();
    }

    /**
     * Get SQL Server errors
     *
     * @param  boolean $asString
     * @return mixed
     */
    public function getSqlSrvErrors($asString = true)
    {
        $errors       = '';
        $errorsAry    = [];
        $sqlSrvErrors = sqlsrv_errors();

        foreach ($sqlSrvErrors as $value) {
            $errorsAry[$value['code']] = stripslashes($value['message']);
            $errors .= 'SQLSTATE: ' . $value['SQLSTATE'] . ', CODE: ' .
                $value['code'] . ' => ' . stripslashes($value['message']) . PHP_EOL;
        }

        return ($asString) ? $errors : $errorsAry;
    }

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    public function escape($value)
    {
        $search  = ['\\', "\n", "\r", "\x00", "\x1a", '\'', '"'];
        $replace = ['\\\\', "\\n", "\\r", "\\x00", "\\x1a", '\\\'', '\\"'];
        return str_replace($search, $replace, $value);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId()
    {
        $this->query('SELECT SCOPE_IDENTITY() as Current_Identity');
        $row = $this->fetch();
        return (isset($row['Current_Identity'])) ? $row['Current_Identity'] : 0;
    }

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        if (null !== $this->statement) {
            return sqlsrv_num_rows($this->statement);
        } else if (null !== $this->result) {
            return sqlsrv_num_rows($this->result);
        } else {
            $this->throwError('Error: The database result resource is not currently set.');
        }
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = sqlsrv_server_info($this->connection);
        return $version['SQLServerName'] . ': ' . $version['SQLServerVersion'];
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
    {
        $tables = [];

        $this->query("SELECT name FROM " . $this->database . ".sysobjects WHERE xtype = 'U'");
        while (($row = $this->fetch())) {
            foreach($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

}