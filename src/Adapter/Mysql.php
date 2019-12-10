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
 * MySQL database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Mysql extends AbstractAdapter
{

    /**
     * Statement result
     * @var boolean
     */
    protected $statementResult = false;

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
    public function connect(array $options = [])
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
    public function setOptions(array $options)
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
     * @return boolean
     */
    public function hasOptions()
    {
        return (isset($this->options['database']) && isset($this->options['username']) && isset($this->options['password']));
    }

    /**
     * Begin a transaction
     *
     * @param  int    $flags
     * @param  string $name
     * @return Mysql
     */
    public function beginTransaction($flags = null, $name = null)
    {
        if ((null !== $flags) && (null !== $name)) {
            $this->connection->begin_transaction($flags, $name);
        } else if (null !== $flags) {
            $this->connection->begin_transaction($flags);
        } else {
            $this->connection->begin_transaction();
        }

        return $this;
    }

    /**
     * Commit a transaction
     *
     * @param  int    $flags
     * @param  string $name
     * @return Mysql
     */
    public function commit($flags = null, $name = null)
    {
        if ((null !== $flags) && (null !== $name)) {
            $this->connection->commit($flags, $name);
        } else if (null !== $flags) {
            $this->connection->commit($flags);
        } else {
            $this->connection->commit();
        }

        return $this;
    }

    /**
     * Rollback a transaction
     *
     * @param  int    $flags
     * @param  string $name
     * @return Mysql
     */
    public function rollback($flags = null, $name = null)
    {
        if ((null !== $flags) && (null !== $name)) {
            $this->connection->rollback($flags, $name);
        } else if (null !== $flags) {
            $this->connection->rollback($flags);
        } else {
            $this->connection->rollback();
        }

        return $this;
    }

    /**
     * Execute a SQL query directly
     *
     * @param  mixed $sql
     * @return Mysql
     */
    public function query($sql)
    {
        $this->statement       = null;
        $this->statementResult = false;

        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        if (!($this->result = $this->connection->query($sql))) {
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->connection->error, $this->connection->errno);
            }
            $this->throwError('Error: ' . $this->connection->errno . ' => ' . $this->connection->error);
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
     * @return Mysql
     */
    public function prepare($sql)
    {
        if ($sql instanceof \Pop\Db\Sql\AbstractSql) {
            $sql = (string)$sql;
        }

        $this->statement = $this->connection->stmt_init();
        if (!$this->statement->prepare($sql)) {
            if (null !== $this->profiler) {
                $this->profiler->addStep();
                $this->profiler->current->setQuery($sql);
                $this->profiler->current->addError($this->statement->error, $this->statement->errno);
            }
            $this->throwError('MySQL Statement Error: ' . $this->statement->errno . ' (#' . $this->statement->error . ')');
        } else if (null !== $this->profiler) {
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
    public function bindParams(array $params)
    {
        $bindParams = [''];

        if (null !== $this->profiler) {
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
    public function execute()
    {
        if (null === $this->statement) {
            $this->throwError('Error: The database statement resource is not currently set');
        }

        $this->statementResult = $this->statement->execute();

        if (!empty($this->statement->error)) {
            if (null !== $this->profiler) {
                $this->profiler->current->addError($this->statement->error, $this->statement->errno);
            }
            $this->throwError('MySQL Statement Error: ' . $this->statement->errno . ' (#' . $this->statement->error . ')');
        }

        if (null !== $this->profiler) {
            $this->profiler->current->finish();
        }

        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @throws Exception
     * @return array
     */
    public function fetch()
    {
        if ((null !== $this->statement) && ($this->statementResult !== false)) {
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
            if (null === $this->result) {
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
            $this->connection->close();
        }

        parent::disconnect();
    }

    /**
     * Escape the value
     *
     * @param  string $value
     * @return string
     */
    public function escape($value)
    {
        return $this->connection->real_escape_string($value);
    }

    /**
     * Return the last ID of the last query
     *
     * @return int
     */
    public function getLastId()
    {
        return $this->connection->insert_id;
    }

    /**
     * Return the number of rows from the last query
     *
     * @return int
     */
    public function getNumberOfRows()
    {
        if (null !== $this->statement) {
            $this->statement->store_result();
            return $this->statement->num_rows;
        } else if (null !== $this->result) {
            return $this->result->num_rows;
        } else {
            $this->throwError('Error: The database result resource is not currently set.');
        }
    }

    /**
     * Return the database version.
     *
     * @return string
     */
    public function getVersion()
    {
        return 'MySQL ' . $this->connection->server_info;
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
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