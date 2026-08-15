<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
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
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 *
 * @property-read ?PredicateSet $where WHERE predicate object (lazily created by subclasses)
 */
abstract class AbstractPredicateClause extends AbstractClause
{

    /**
     * WHERE predicate object
     * @var ?PredicateSet
     */
    protected ?PredicateSet $wherePredicate = null;

    /**
     * Access the WHERE clause
     *
     * @param  mixed $where
     * @return AbstractPredicateClause
     */
    public function where(mixed $where = null): AbstractPredicateClause
    {
        if ($where instanceof PredicateSet) {
            $this->wherePredicate = $where;
        } else {
            if ($this->wherePredicate === null) {
                $this->wherePredicate = new Where($this);
            }

            if ($where !== null) {
                if (is_string($where)) {
                    $tokens = Parser\Keyword::split($where);
                    if (count($tokens) > 1) {
                        foreach ($tokens as $i => $token) {
                            if (($i > 0) && (($tokens[$i - 1] === 'AND') || ($tokens[$i - 1] === 'OR'))) {
                                if ($tokens[$i - 1] === 'AND') {
                                    $this->wherePredicate->and($token);
                                } else {
                                    $this->wherePredicate->or($token);
                                }
                            } else if (($token !== 'AND') && ($token !== 'OR')) {
                                $this->wherePredicate->add($token);
                            }
                        }
                    } else {
                        $this->wherePredicate->add($where);
                    }
                } else if (is_array($where)) {
                    $this->wherePredicate->addExpressions($where);
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
        if ($this->wherePredicate === null) {
            $this->wherePredicate = new Where($this);
        }

        if ($where !== null) {
            if (is_string($where)) {
                $this->wherePredicate->and($where);
            } else if (is_array($where)) {
                foreach ($where as $w) {
                    $this->wherePredicate->and($w);
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
        if ($this->wherePredicate === null) {
            $this->wherePredicate = new Where($this);
        }

        if ($where !== null) {
            if (is_string($where)) {
                $this->wherePredicate->or($where);
            } else if (is_array($where)) {
                foreach ($where as $w) {
                    $this->wherePredicate->or($w);
                }
            }
        }

        return $this;
    }

}
