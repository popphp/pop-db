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
 * Oracle database adapter class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Oracle extends AbstractAdapter
{

    /**
     * Constructor
     *
     * Instantiate the Oracle database connection object
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

        $connectionString = $options['host'] . '/' . $options['database'];
        $oci_connect      = (isset($options['persist']) && ($options['persist'])) ? 'oci_pconnect' : 'oci_connect';

        if (isset($options['character_set']) && isset($options['session_mode'])) {
            $this->connection = $oci_connect(
                $options['username'], $options['password'], $connectionString, $options['character_set'], $options['session_mode']
            );
        } else if (isset($options['character_set'])) {
            $this->connection = $oci_connect(
                $options['username'], $options['password'], $connectionString, $options['character_set']
            );
        } else if (isset($options['session_mode'])) {
            $this->connection = $oci_connect(
                $options['username'], $options['password'], $connectionString, null, $options['session_mode']
            );
        } else {
            $this->connection = $oci_connect($options['username'], $options['password'], $connectionString);
        }

        if ($this->connection == false) {
            $this->throwError('Oracle Connection Error: ' . oci_error());
        }
    }

    /**
     * Execute a SQL query directly
     *
     * @param  string $sql
     * @return Oracle
     */
    public function query($sql)
    {
        $this->statement = oci_parse($this->connection, $sql);
        if (!($this->result = oci_execute($this->statement))) {
            $this->throwError('Error: ' . oci_error($this->connection));
        }
        return $this;
    }

    /**
     * Prepare a SQL query
     *
     * @param  string $sql
     * @return Oracle
     */
    public function prepare($sql)
    {
        $this->statement = oci_parse($this->connection, $sql);
        return $this;
    }

    /**
     * Bind parameters to a prepared SQL query
     *
     * @param  array $params
     * @return Oracle
     */
    public function bindParams(array $params)
    {
        foreach ($params as $dbColumnName => $dbColumnValue) {
            ${$dbColumnName} = $dbColumnValue;
            oci_bind_by_name($this->statement, ':' . $dbColumnName, ${$dbColumnName});
        }

        return $this;
    }

    /**
     * Execute a prepared SQL query
     *
     * @return Oracle
     */
    public function execute()
    {
        if (null === $this->statement) {
            $this->throwError('Error: The database statement resource is not currently set.');
        }

        oci_execute($this->statement);

        return $this;
    }

    /**
     * Fetch and return a row from the result
     *
     * @return array
     */
    public function fetch()
    {
        if (!isset($this->statement)) {
            $this->throwError('Error: The database result resource is not currently set.');
        }

        return oci_fetch_array($this->statement, OCI_RETURN_NULLS+OCI_ASSOC);
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
            oci_close($this->connection);
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
            return oci_num_rows($this->statement);
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
        return oci_server_version($this->connection);
    }

    /**
     * Return the tables in the database
     *
     * @return array
     */
    public function getTables()
    {
        $tables = [];

        $this->query("SELECT TABLE_NAME FROM USER_TABLES");
        while (($row = $this->fetch())) {
            foreach($row as $value) {
                $tables[] = $value;
            }
        }

        return $tables;
    }

}