<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
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
     * @return AbstractPredicateClause
     */
    public function where($where = null)
    {
        if (null === $this->where) {
            $this->where = new Where($this);
        }

        if (null !== $where) {
            if (is_string($where)) {
                if ((stripos($where, ' AND ') !== false) || (stripos($where, ' OR ') !== false)) {
                    $expressions = array_map('trim', preg_split(
                        '/(AND|OR)/', $where, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                    ));
                    foreach ($expressions as $i => $expression) {
                        if (isset($expressions[$i - 1]) && (strtoupper($expressions[$i - 1]) == 'AND')) {
                            $this->where->and($expression);
                        } else if (isset($expressions[$i - 1]) && (strtoupper($expressions[$i - 1]) == 'OR')) {
                            $this->where->or($expression);
                        } else if (($expression != 'AND') && ($expression != 'OR')) {
                            $this->where->add($expression);
                        }
                    }
                } else {
                    $this->where->add($where);
                }
            } else if (is_array($where)) {
                $this->where->addExpressions($where);
            }
        }

        return $this;
    }

    /**
     * Access the WHERE clause with AND
     *
     * @param  mixed $where
     * @return AbstractPredicateClause
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

        return $this;
    }

    /**
     * Access the WHERE clause with OR
     *
     * @param  mixed $where
     * @return AbstractPredicateClause
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

        return $this;
    }

}