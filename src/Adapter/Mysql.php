<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
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
    public function __construct(array $options)
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

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            $this->throwError('Error: The proper database credentials were not passed.');
        }

        $this->connection = new \mysqli(
            $options['host'],     $options['username'], $options['password'],
            $options['database'], $options['port'],     $options['socket']
        );

        if ($this->connection->connect_error != '') {
            $this->throwError('MySQL Connection Error: ' . $this->connection->connect_error . ' (#' . $this->connection->connect_errno . ')');
        }
    }

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return Mysql
     */
    public function query($sql)
    {
        $this->statement       = null;
        $this->statementResult = false;

        if (!($this->result = $this->connection->query($sql))) {
            $this->throwError('Error: ' . $this->connection->errno . ' => ' . $this->connection->error);
        }
        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @return Mysql
     */
    public function prepare($sql)
    {
        $this->statement = $this->connection->stmt_init();
        $this->statement->prepare($sql);
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

        $i = 1;
        foreach ($params as $dbColumnName => $dbColumnValue) {
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

        call_user_func_array([$this->statement, 'bind_param'], $bindParams);

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
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        $this->statementResult = $this->statement->execute();
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