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
 * Abstract clause class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractPredicateClause extends AbstractClause
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
     * @return Where
     */
    public function where($where = null)
    {
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        if (null !== $where) {
            if (is_string($where)) {
                $this->where->add($where);
            } else if (is_array($where)) {
                $this->where->addExpressions($where);
            }
        }

        return $this->where;
    }

    /**
     * Access the WHERE clause with AND
     *
     * @param  mixed $where
     * @return Where
     */
    public function andWhere($where = null)
    {
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        if (null !== $where) {
            if (is_string($where)) {
                $this->where->and($where);
            } else if (is_array($where)) {
                foreach ($where as $w) {
                    $this->where->and($w);
                }
            }
        }

        return $this->where;
    }

    /**
     * Access the WHERE clause with OR
     *
     * @param  mixed $where
     * @return Where
     */
    public function orWhere($where = null)
    {
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        if (null !== $where) {
            if (is_string($where)) {
                $this->where->or($where);
            } else if (is_array($where)) {
                foreach ($where as $w) {
                    $this->where->or($w);
                }
            }
        }

        return $this->where;
    }

}