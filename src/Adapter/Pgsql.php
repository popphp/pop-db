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
 * PostgreSQL database adapter class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Pgsql extends AbstractAdapter
{

    /**
     * Constructor
     *
     * Instantiate the PostgreSQL database connection object
     *
     * @param  array $options
     * @throws Exception
     * @return Pgsql
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
        }

        $connectionString = "host=" . $options['host'] . " dbname=" . $options['database'] .
            " user=" . $options['username'] . " password=" . $options['password'];

        if (isset($options['port'])) {
            $connectionString .= " port=" . $options['port'];
        }
        if (isset($options['hostaddr'])) {
            $connectionString .= " hostaddr=" . $options['hostaddr'];
        }
        if (isset($options['connect_timeout'])) {
            $connectionString .= " connect_timeout=" . $options['connect_timeout'];
        }
        if (isset($options['options'])) {
            $connectionString .= " options=" . $options['options'];
        }
        if (isset($options['sslmode'])) {
            $connectionString .= " sslmode=" . $options['sslmode'];
        }

        $pg_connect = (isset($options['persist']) && ($options['persist'])) ? 'pg_pconnect' : 'pg_connect';

        if (isset($options['type'])) {
            $this->connection = $pg_connect($connectionString, $options['type']);
        } else {
            $this->connection = $pg_connect($connectionString);
        }

        if (!$this->connection) {
            $this->error = 'PostgreSQL Connection Error: Unable to connect to the database.';
            $this->throwError();
        }
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = pg_version($this->connection);
        return 'PostgreSQL ' . $version['server'];
    }

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            pg_close($this->connection);
        }

        parent::disconnect();
    }

}