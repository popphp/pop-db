<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Delete extends AbstractPredicateClause
{

    /**
     * Set from table
     *
     * @param  mixed  $table
     * @return Delete
     */
    public function from($table)
    {
        $this->setTable($table);
        return $this;
    }

    /**
     * Render the DELETE statement
     *
     * @return string
     */
    public function render()
    {
        // Start building the DELETE statement
        $sql = 'DELETE FROM ' . $this->quoteId($this->table);

        // Build any WHERE clauses
        if (null !== $this->where) {
            $sql .= ' WHERE ' . $this->where;
        }

        return $sql;
    }

    /**
     * Render the DELETE statement
     *
     * @return string
     */
    public function __toString()
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
    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'where':
                if (null === $this->where) {
                    $this->where = new Where($this);
                }
                return $this->where;
                break;
            default:
                throw new Exception('Not a valid property for this object.');
        }
    }

}