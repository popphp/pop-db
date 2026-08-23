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
namespace Pop\Db\Sql;

/**
 * Delete class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
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
        if ($this->wherePredicate !== null) {
            $sql .= ' WHERE ' . $this->wherePredicate;
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
                if ($this->wherePredicate === null) {
                    $this->wherePredicate = new Where($this);
                }
                return $this->wherePredicate;
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

}
