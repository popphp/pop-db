<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

/**
 * Predicate set class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
class PredicateSet
{

    /**
     * SQL object
     * @var AbstractSql
     */
    protected $sql = null;

    /**
     * Predicates
     * @var array
     */
    protected $predicates = [];

    /**
     * Nested predicate sets
     * @var array
     */
    protected $predicateSets = [];

    /**
     * Conjunction
     * @var string
     */
    protected $conjunction = null;

    /**
     * Next conjunction
     * @var string
     */
    protected $nextConjunction = 'AND';

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  AbstractSql $sql
     * @param  mixed       $predicates
     * @param  string      $conjunction
     */
    public function __construct(AbstractSql $sql, $predicates = null, $conjunction = null)
    {
        $this->sql = $sql;

        if ($predicates !== null) {
            if (is_array($predicates)) {
                $this->addPredicates($predicates);
            } else {
                $this->addPredicate($predicates);
            }
        }

        if ($conjunction !== null) {
            $this->setConjunction($conjunction);
        }
    }

    /**
     * Add a predicate from a string expression
     *
     * @param  string $expression
     * @return PredicateSet
     */
    public function add($expression)
    {
        ['column' => $column, 'operator' => $operator, 'value' => $value] = Parser\Expression::parse($expression);

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($this->sql->isParameter($v, $column)) {
                    $value[$k] = $this->sql->getParameter($v, $column);
                }
            }
        } else {
            if ($this->sql->isParameter($value, $column)) {
                $value = $this->sql->getParameter($value, $column);
            }
        }

        switch ($operator) {
            case '=':
                $this->equalTo($column, $value);
                break;
            case '!=':
                $this->notEqualTo($column, $value);
                break;
            case '>':
                $this->greaterThan($column, $value);
                break;
            case '>=':
                $this->greaterThanOrEqualTo($column, $value);
                break;
            case '<=':
                $this->lessThanOrEqualTo($column, $value);
                break;
            case '<':
                $this->lessThan($column, $value);
                break;
            case 'LIKE':
                $this->like($column, $value);
                break;
            case 'NOT LIKE':
                $this->notLike($column, $value);
                break;
            case 'BETWEEN':
                $this->between($column, $value[0], $value[1]);
                break;
            case 'NOT BETWEEN':
                $this->notBetween($column, $value[0], $value[1]);
                break;
            case 'IN':
                $this->in($column, $value);
                break;
            case 'NOT IN':
                $this->notIn($column, $value);
                break;
            case 'IS NULL':
                $this->isNull($column);
                break;
            case 'IS NOT NULL':
                $this->isNotNull($column);
                break;

        }

        return $this;
    }

    /**
     * Add a predicates from string expressions
     *
     * @param  array $expressions
     * @return PredicateSet
     */
    public function addExpressions(array $expressions)
    {
        foreach ($expressions as $expression) {
            $this->add($expression);
        }

        return $this;
    }

    /**
     * Add an AND predicate from a string expression
     *
     * @param  string $expression
     * @return PredicateSet
     */
    public function and($expression = null)
    {
        $this->setNextConjunction('AND');
        if ($expression !== null) {
            $this->add($expression);
        }
        return $this;
    }

    /**
     * Add an OR predicate from a string expression
     *
     * @param  string $expression
     * @return PredicateSet
     */
    public function or($expression = null)
    {
        $this->setNextConjunction('OR');
        if ($expression !== null) {
            $this->add($expression);
        }
        return $this;
    }

    /**
     * Predicate for =
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function equalTo($column, $value)
    {
        return $this->addPredicate(new Predicate\EqualTo([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for !=
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function notEqualTo($column, $value)
    {
        return $this->addPredicate(new Predicate\NotEqualTo([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for >
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function greaterThan($column, $value)
    {
        return $this->addPredicate(new Predicate\GreaterThan([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for >=
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function greaterThanOrEqualTo($column, $value)
    {
        return $this->addPredicate(new Predicate\GreaterThanOrEqualTo([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for <
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function lessThan($column, $value)
    {
        return $this->addPredicate(new Predicate\LessThan([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for <=
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function lessThanOrEqualTo($column, $value)
    {
        return $this->addPredicate(new Predicate\LessThanOrEqualTo([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for LIKE
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function like($column, $value)
    {
        return $this->addPredicate(new Predicate\Like([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for NOT LIKE
     *
     * @param  string $column
     * @param  string $value
     * @return PredicateSet
     */
    public function notLike($column, $value)
    {
        return $this->addPredicate(new Predicate\NotLike([$column, $value], $this->nextConjunction));
    }

    /**
     * Predicate for BETWEEN
     *
     * @param  string $column
     * @param  string $value1
     * @param  string $value2
     * @return PredicateSet
     */
    public function between($column, $value1, $value2)
    {
        return $this->addPredicate(new Predicate\Between([$column, $value1, $value2], $this->nextConjunction));
    }

    /**
     * Predicate for NOT BETWEEN
     *
     * @param  string $column
     * @param  string $value1
     * @param  string $value2
     * @return PredicateSet
     */
    public function notBetween($column, $value1, $value2)
    {
        return $this->addPredicate(new Predicate\NotBetween([$column, $value1, $value2], $this->nextConjunction));
    }

    /**
     * Predicate for IN
     *
     * @param  string $column
     * @param  mixed  $values
     * @return PredicateSet
     */
    public function in($column, $values)
    {
        return $this->addPredicate(new Predicate\In([$column, $values], $this->nextConjunction));
    }

    /**
     * Predicate for NOT IN
     *
     * @param  string $column
     * @param  mixed  $values
     * @return PredicateSet
     */
    public function notIn($column, $values)
    {
        return $this->addPredicate(new Predicate\NotIn([$column, $values], $this->nextConjunction));
    }

    /**
     * Predicate for IS NULL
     *
     * @param  string $column
     * @return PredicateSet
     */
    public function isNull($column)
    {
        return $this->addPredicate(new Predicate\IsNull($column, $this->nextConjunction));
    }

    /**
     * Predicate for IS NOT NULL
     *
     * @param  string $column
     * @return PredicateSet
     */
    public function isNotNull($column)
    {
        return $this->addPredicate(new Predicate\IsNotNull($column, $this->nextConjunction));
    }

    /**
     * Add AND predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    public function andPredicate(Predicate\AbstractPredicate $predicate)
    {
        $predicate->setConjunction('AND');
        return $this->addPredicate($predicate);
    }

    /**
     * Add OR predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    public function orPredicate(Predicate\AbstractPredicate $predicate)
    {
        $predicate->setConjunction('OR');
        return $this->addPredicate($predicate);
    }

    /**
     * Add predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @return PredicateSet
     */
    public function addPredicate(Predicate\AbstractPredicate $predicate)
    {
        $values = $predicate->getValues();

        if (is_array($values)) {
            $column = array_shift($values);

            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($this->sql->isParameter($v, $column)) {
                            $values[$key][$k] = $this->sql->getParameter($v, $column);
                        }
                    }
                } else {
                    if ($this->sql->isParameter($value, $column)) {
                        $values[$key] = $this->sql->getParameter($value, $column);
                    }
                }
            }

            $predicate->setValues(array_merge([$column], $values));
        }

        $this->predicates[] = $predicate;
        return $this;
    }

    /**
     * Add predicates
     *
     * @param  array $predicates
     * @return PredicateSet
     */
    public function addPredicates(array $predicates)
    {
        foreach ($predicates as $predicate) {
            $this->addPredicate($predicate);
        }

        return $this;
    }

    /**
     * Add predicate set
     *
     * @param  PredicateSet $predicateSet
     * @return PredicateSet
     */
    public function addPredicateSet(PredicateSet $predicateSet)
    {
        $this->predicateSets[] = $predicateSet;
        return $this;
    }

    /**
     * Add predicate sets
     *
     * @param  array $predicateSets
     * @return PredicateSet
     */
    public function addPredicateSets(array $predicateSets)
    {
        foreach ($predicateSets as $predicateSet) {
            $this->addPredicateSet($predicateSet);
        }

        return $this;
    }

    /**
     * Add a nested predicate set
     *
     * @param  string $conjunction
     * @return PredicateSet
     */
    public function nest($conjunction = 'AND')
    {
        $predicateSet = new self($this->sql, null, $conjunction);
        $this->addPredicateSet($predicateSet);
        return $predicateSet;
    }

    /**
     * Add a nested predicate set with the AND conjunction
     *
     * @return PredicateSet
     */
    public function andNest()
    {
        return $this->nest('AND');
    }

    /**
     * Add a nested predicate set with the OR conjunction
     *
     * @return PredicateSet
     */
    public function orNest()
    {
        return $this->nest('OR');
    }

    /**
     * Get the conjunction
     *
     * @param  string $conjunction
     * @return PredicateSet
     */
    public function setConjunction($conjunction)
    {
        if ((strtoupper($conjunction) != 'OR') && (strtoupper($conjunction) != 'AND')) {
            throw new Exception("Error: The conjunction must be 'AND' or 'OR'. '" . $conjunction . "' is not allowed.");
        }

        $this->conjunction = $conjunction;

        return $this;
    }

    /**
     * Get the conjunction
     *
     * @return string
     */
    public function getConjunction()
    {
        return $this->conjunction;
    }

    /**
     * Get the next conjunction
     *
     * @param  string $conjunction
     * @return PredicateSet
     */
    public function setNextConjunction($conjunction)
    {
        if ((strtoupper($conjunction) != 'OR') && (strtoupper($conjunction) != 'AND')) {
            throw new Exception("Error: The conjunction must be 'AND' or 'OR'. '" . $conjunction . "' is not allowed.");
        }

        $this->nextConjunction = $conjunction;

        return $this;
    }

    /**
     * Get the next conjunction
     *
     * @return string
     */
    public function getNextConjunction()
    {
        return $this->nextConjunction;
    }

    /**
     * Has predicates
     *
     * @return bool
     */
    public function hasPredicates()
    {
        return (count($this->predicates) > 0);
    }

    /**
     * Get predicates
     *
     * @return array
     */
    public function getPredicates()
    {
        return $this->predicates;
    }

    /**
     * Has predicates
     *
     * @return bool
     */
    public function hasPredicateSets()
    {
        return (count($this->predicateSets) > 0);
    }

    /**
     * Get predicates
     *
     * @return array
     */
    public function getPredicateSets()
    {
        return $this->predicateSets;
    }

    /**
     * Predicate set render method
     *
     * @return string
     */
    public function render()
    {
        $predicateString = null;

        foreach ($this->predicates as $i => $predicate) {
            $predicateString .= ($i == 0) ?
                $predicate->render($this->sql) : ' ' . $predicate->getConjunction() . ' ' . $predicate->render($this->sql);
        }

        foreach ($this->predicateSets as $i => $predicateSet) {
            if (empty($predicateSet->getConjunction())) {
                throw new Exception('Error: The combination conjunction was not set for this predicate set.');
            }
            $predicateString .= ' ' . $predicateSet->getConjunction() . ' ' . $predicateSet->render();
        }

        if (((count($this->predicateSets) > 0) && (count($this->predicates) > 0)) ||
            (count($this->predicateSets) > 1) || (count($this->predicates) > 1)) {
            return '(' . $predicateString . ')';
        } else {
            return $predicateString;
        }
    }

    /**
     * Return predicate set string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}