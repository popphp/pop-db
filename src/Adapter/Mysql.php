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
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Mysql extends AbstractAdapter
{

    /**
     * Constructor
     *
     * Instantiate the MySQL database connection object using mysqli
     *
     * @param  array $options
     * @throws Exception
     * @return Mysql
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
            throw new Exception('Error: The proper database credentials were not passed.');
        }

        $this->connection = new \mysqli(
            $options['host'],     $options['username'], $options['password'],
            $options['database'], $options['port'],     $options['socket']
        );

        if ($this->connection->connect_error != '') {
            $this->error = 'MySQL Connection Error: ' . $this->connection->connect_error .
                ' (#' . $this->connection->connect_errno . ')';
            $this->throwError();
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

}