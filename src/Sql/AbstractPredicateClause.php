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
namespace Pop\Db\Sql;

/**
 * Abstract clause class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 * @property   $where mixed
 */
abstract class AbstractPredicateClause extends AbstractClause
{

    /**
     * WHERE predicate object
     * @var ?Where
     */
    protected ?Where $where = null;

    /**
     * Access the WHERE clause
     *
     * @param  mixed $where
     * @return AbstractPredicateClause
     */
    public function where(mixed $where = null): AbstractPredicateClause
    {
        if ($this->where === null) {
            $this->where = new Where($this);
        }

        if ($where !== null) {
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
    public function andWhere(mixed $where = null): AbstractPredicateClause
    {
        if ($this->where === null) {
            $this->where = new Where($this);
        }

        if ($where !== null) {
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
    public function orWhere(mixed $where = null): AbstractPredicateClause
    {
        if ($this->where === null) {
            $this->where = new Where($this);
        }

        if ($where !== null) {
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
