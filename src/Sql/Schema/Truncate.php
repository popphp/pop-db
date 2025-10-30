<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Schema;

/**
 * Schema TRUNCATE table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
class Truncate extends AbstractTable
{

    /**
     * CASCADE flag
     * @var bool
     */
    protected bool $cascade  = false;

    /**
     * Set the CASCADE flag
     *
     * @return Truncate
     */
    public function cascade(): Truncate
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
        return 'TRUNCATE TABLE ' . $this->quoteId($this->table) .
            ((($this->isPgsql()) && ($this->cascade)) ? ' CASCADE' : null) . ';' . PHP_EOL;
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
