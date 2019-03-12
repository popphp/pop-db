<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema;

/**
 * Schema DROP table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Drop extends AbstractTable
{

    /**
     * IF EXISTS flag
     * @var boolean
     */
    protected $ifExists = false;

    /**
     * CASCADE flag
     * @var boolean
     */
    protected $cascade  = false;

    /**
     * Set the IF EXISTS flag
     *
     * @return Drop
     */
    public function ifExists()
    {
        $this->ifExists = true;
        return $this;
    }

    /**
     * Set the CASCADE flag
     *
     * @return Drop
     */
    public function cascade()
    {
        $this->cascade = true;
        return $this;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render()
    {
        return 'DROP TABLE ' . ((($this->ifExists) && ($this->dbType != self::SQLSRV)) ? 'IF EXISTS ' : null)
        . $this->quoteId($this->table) . ((($this->isPgsql()) && ($this->cascade)) ? ' CASCADE' : null) . ';' . PHP_EOL;
    }

    /**
     * Render the table schema to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}