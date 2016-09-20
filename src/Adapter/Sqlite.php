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
 * SQLite database adapter class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Sqlite extends AbstractAdapter
{

    /**
     * Constructor
     *
     * Instantiate the SQLite database connection object using SQLite3
     *
     * @param  array $options
     * @throws Exception
     * @return Sqlite
     */
    public function __construct(array $options)
    {
        if (!isset($options['database'])) {
            throw new Exception('Error: The database file was not passed.');
        } else if (!file_exists($options['database'])) {
            throw new Exception('Error: The database file does not exists.');
        }

        $flags = (isset($options['flags'])) ? $options['flags'] : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $key   = (isset($options['key']))   ? $options['key'] : null;

        $this->connection = new \SQLite3($options['database'], $flags, $key);
    }

    /**
     * Return the database version
     *
     * @return string
     */
    public function getVersion()
    {
        $version = $this->connection->version();
        return 'SQLite ' . $version['versionString'];
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