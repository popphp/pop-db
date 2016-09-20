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
 * Db adapter interface
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
interface AdapterInterface
{

    /**
     * Determine whether or not connected
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Get the connection object/resource
     *
     * @return mixed
     */
    public function getConnection();

    /**
     * Determine whether or not a statement resource exists
     *
     * @return boolean
     */
    public function hasStatement();

    /**
     * Get the statement object/resource
     *
     * @return mixed
     */
    public function getStatement();

    /**
     * Determine whether or not a result resource exists
     *
     * @return boolean
     */
    public function hasResult();

    /**
     * Get the result object/resource
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Determine whether or not there is an error
     *
     * @return boolean
     */
    public function hasError();

    /**
     * Get the error
     *
     * @return mixed
     */
    public function getError();

    /**
     * Throw a database error exception
     *
     * @throws Exception
     * @return void
     */
    public function throwError();

    /**
     * Return the database version.
     *
     * @return string
     */
    public function getVersion();

    /**
     * Disconnect from the database
     *
     * @return void
     */
    public function disconnect();

}