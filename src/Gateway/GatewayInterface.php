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
     * Set 1:1 relationships
     *
     * @param  array $oneToOne
     * @return AbstractGateway
     */
    public function setOneToOne(array $oneToOne);

    /**
     * Set 1:many relationships
     *
     * @param  array $oneToMany
     * @return AbstractGateway
     */
    public function setOneToMany(array $oneToMany);

    /**
     * Determine if the table has 1:1 relationships
     *
     * @return boolean
     */
    public function hasOneToOne();

    /**
     * Determine if the table has 1:many relationships
     *
     * @return boolean
     */
    public function hasOneToMany();

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