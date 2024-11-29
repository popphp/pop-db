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
 * Schema RENAME table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Rename extends AbstractTable
{

    /**
     * Rename table name
     * @var ?string
     */
    protected ?string $to = null;

    /**
     * Set the rename table name
     *
     * @param  string $table
     * @return Rename
     */
    public function to(string $table): Rename
    {
        $this->to = $table;
        return $this;
    }

    /**
     * Get the rename table name
     *
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    public function render(): string
    {
        return ($this->isMysql()) ?
            'RENAME TABLE ' . $this->quoteId($this->table) . ' TO ' . $this->quoteId($this->to) . ';' . PHP_EOL :
            'ALTER TABLE ' . $this->quoteId($this->table) . ' RENAME TO ' . $this->quoteId($this->to) . ';' . PHP_EOL;
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
