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
namespace Pop\Db\Sql;

/**
 * Delete class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Delete extends AbstractClause
{

    /**
     * WHERE predicate object
     * @var Where
     */
    protected $where = null;

    /**
     * Access the WHERE clause
     *
     * @param  mixed $where
     * @return Delete
     */
    public function where($where = null)
    {
        if (null !== $where) {
            if ($where instanceof Where) {
                $this->where = $where;
            } else {
                if (null === $this->where) {
                    $this->where = (new Where($this))->add($where);
                } else {
                    $this->where->add($where);
                }
            }
        }
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        return $this;
    }

    /**
     * Access the WHERE clause with AND
     *
     * @param  mixed $where
     * @return Delete
     */
    public function andWhere($where = null)
    {
        if ($this->where->hasPredicates()) {
            $this->where->getLastPredicateSet()->setCombine('AND');
        }
        $this->where($where);
        return $this;
    }

    /**
     * Access the WHERE clause with OR
     *
     * @param  mixed $where
     * @return Delete
     */
    public function orWhere($where = null)
    {
        if ($this->where->hasPredicates()) {
            $this->where->getLastPredicateSet()->setCombine('OR');
        }
        $this->where($where);
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