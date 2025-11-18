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
namespace Pop\Db\Sql;

/**
 * Delete class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
class Delete extends AbstractPredicateClause
{

    /**
     * Set from table
     *
     * @param  mixed $table
     * @return Delete
     */
    public function from(mixed $table): Delete
    {
        $this->setTable($table);
        return $this;
    }

    /**
     * Render the DELETE statement
     *
     * @return string
     */
    public function render(): string
    {
        // Start building the DELETE statement
        $sql = 'DELETE FROM ' . $this->quoteId($this->table);

        // Build any WHERE clauses
        if ($this->where !== null) {
            $sql .= ' WHERE ' . $this->where;
        }

        return $sql;
    }

    /**
     * Render the DELETE statement
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Magic method to access $where property
     *
     * @param  string $name
     * @throws Exception
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        switch (strtolower($name)) {
            case 'where':
                if ($this->where === null) {
                    $this->where = new Where($this);
                }
                return $this->where;
                break;
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

}
