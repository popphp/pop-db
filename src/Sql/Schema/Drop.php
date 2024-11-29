<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Drop extends AbstractTable
{

    /**
     * IF EXISTS flag
     * @var bool
     */
    protected bool $ifExists = false;

    /**
     * CASCADE flag
     * @var bool
     */
    protected bool $cascade  = false;

    /**
     * Set the IF EXISTS flag
     *
     * @return Drop
     */
    public function ifExists(): Drop
    {
        $this->ifExists = true;
        return $this;
    }

    /**
     * Set the CASCADE flag
     *
     * @return Drop
     */
    public function cascade(): Drop
    {
        $this->cascade = true;
        return $this;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render(): string
    {
        return 'DROP TABLE ' . ((($this->ifExists) && ($this->dbType != self::SQLSRV)) ? 'IF EXISTS ' : null)
            . $this->quoteId($this->table) . ((($this->isPgsql()) && ($this->cascade)) ? ' CASCADE' : null) . ';' . PHP_EOL;
    }

    /**
     * Render the table schema to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
