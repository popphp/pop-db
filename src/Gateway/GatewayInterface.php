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
namespace Pop\Db\Gateway;

use Pop\Db\Sql;

/**
 * Db gateway interface
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
interface GatewayInterface
{

    /**
     * Get the SQL object
     *
     * @return Sql
     */
    public function getSql();

    /**
     * Get the SQL object (alias method)
     *
     * @return Sql
     */
    public function sql();

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable();

    /**
     * Get table info
     *
     * @return array
     */
    public function getTableInfo();

}