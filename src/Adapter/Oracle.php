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
 * @package    Pop_Db
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
     * @throws Exception
     * @return Oracle
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
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
            $this->error = 'Oracle Connection Error: Unable to connect to the database. ' . oci_error();
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
        return oci_server_version($this->connection);
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

}