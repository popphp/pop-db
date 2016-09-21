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
 * SQL Server database adapter class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Sqlsrv extends AbstractAdapter
{

    /**
     * Constructor
     *
     * Instantiate the SQL Server database connection object
     *
     * @param  array $options
     * @throws Exception
     * @return Sqlsrv
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'])) {
            $options['host'] = 'localhost';
        }

        if (!isset($options['database']) || !isset($options['username']) || !isset($options['password'])) {
            throw new Exception('Error: The proper database credentials were not passed.');
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

        if ($this->connection == false) {
            $this->setError('SQL Server Connection Error: Unable to connect to the database.' . PHP_EOL . $this->getSqlSrvErrors())
                 ->throwError();
        }
    }

    /**
     * Get SQL Server errors
     *
     * @param  boolean $asString
     * @return mixed
     */
    public function getSqlSrvErrors($asString = true)
    {
        $errors   = null;
        $errorAry = sqlsrv_errors();

        foreach ($errorAry as $value) {
            $errors .= 'SQLSTATE: ' . $value['SQLSTATE'] . ', CODE: ' .
                $value['code'] . ' => ' . stripslashes($value['message']) . PHP_EOL;
        }

        return ($asString) ? $errors : $errorAry;
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

}