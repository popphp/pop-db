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
 * PDO database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Pdo extends AbstractAdapter
{

    /**
     * PDO DSN
     * @var string
     */
    protected $dsn = null;

    /**
     * PDO database type
     * @var string
     */
    protected $type = null;

    /**
     * Statement placeholder
     * @var string
     */
    protected $placeholder = null;

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
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return Pdo
     */
    public function query($sql)
    {
        $sth = $this->connection->prepare($sql);

        if (!($sth->execute())) {
            $this->buildError($sth->errorCode(), $sth->errorInfo())
                 ->throwError();
        } else {
            $this->result = $sth;
        }

        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @param  array  $attribs
     * @return Pdo
     */
    public function prepare($sql, $attribs = null)
    {
        if (strpos($sql, '?') !== false) {
            $this->placeholder = '?';
        } else if (strpos($sql, ':') !== false) {
            $this->placeholder = ':';
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
        if ($this->placeholder == '?') {
            $i = 1;
            foreach ($params as $dbColumnName => $dbColumnValue) {
                ${$dbColumnName} = $dbColumnValue;
                $this->statement->bindParam($i, ${$dbColumnName});
                $i++;
            }
        } else if ($this->placeholder == ':') {
            foreach ($params as $dbColumnName => $dbColumnValue) {
                ${$dbColumnName} = $dbColumnValue;
                $this->statement->bindParam(':' . $dbColumnName, ${$dbColumnName});
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
        $this->statement->bindValue($param, $value, $dataType);
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

        $this->result = $this->statement->execute();
        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    public function fetch()
    {
        if (null === $this->result) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        return $this->result->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch and return all rows from the result
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
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
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect()
    {
        parent::disconnect();
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
     * Build the error
     *
     * @param  string $code
     * @param  array  $info
     * @throws Exception
     * @return Pdo
     */
    protected function buildError($code = null, $info = null)
    {
        $errorMessage = null;

        if ((null === $code) && (null === $info)) {
            $errorCode = $this->connection->errorCode();
            $errorInfo = $this->connection->errorInfo();
        } else {
            $errorCode = $code;
            $errorInfo = $info;
        }

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

        $this->setError('Error: ' . $errorCode . ' => ' . $errorMessage);
        return $this;
    }

}