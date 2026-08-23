<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
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
     * SQLite has no TRUNCATE TABLE statement, so DELETE FROM is used instead to achieve
     * the same effect of removing all rows from the table.
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->isSqlite()) {
            return 'DELETE FROM ' . $this->quoteId($this->table) . ';' . PHP_EOL;
        }

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
