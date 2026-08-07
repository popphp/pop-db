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
 * Abstract clause class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 * @property   $where mixed
 */
abstract class AbstractPredicateClause extends AbstractClause
{

    /**
     * WHERE predicate object
     * @var ?PredicateSet
     */
    protected ?PredicateSet $where = null;

    /**
     * Access the WHERE clause
     *
     * @param  mixed $where
     * @return AbstractPredicateClause
     */
    public function where(mixed $where = null): AbstractPredicateClause
    {
        if ($where instanceof PredicateSet) {
            $this->where = $where;
        } else {
            if ($this->where === null) {
                $this->where = new Where($this);
            }

            if ($where !== null) {
                if (is_string($where)) {
                    $tokens = Parser\Keyword::split($where);
                    if (count($tokens) > 1) {
                        foreach ($tokens as $i => $token) {
                            if (($i > 0) && (($tokens[$i - 1] === 'AND') || ($tokens[$i - 1] === 'OR'))) {
                                if ($tokens[$i - 1] === 'AND') {
                                    $this->where->and($token);
                                } else {
                                    $this->where->or($token);
                                }
                            } else if (($token !== 'AND') && ($token !== 'OR')) {
                                $this->where->add($token);
                            }
                        }
                    } else {
                        $this->where->add($where);
                    }
                } else if (is_array($where)) {
                    $this->where->addExpressions($where);
                }
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
