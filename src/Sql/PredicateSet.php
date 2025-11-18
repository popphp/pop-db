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

use Pop\Db\Sql\Predicate\EqualTo;

/**
 * Predicate set class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
class PredicateSet
{

    /**
     * SQL object
     * @var ?AbstractSql
     */
    protected ?AbstractSql $sql = null;

    /**
     * Predicates
     * @var array
     */
    protected array $predicates = [];

    /**
     * Nested predicate sets
     * @var array
     */
    protected array $predicateSets = [];

    /**
     * Conjunction
     * @var ?string
     */
    protected ?string $conjunction = null;

    /**
     * Next conjunction
     * @var string
     */
    protected string $nextConjunction = 'AND';

    /**
     * Parameters (for binding)
     * @var array
     */
    protected array $parameters = [];

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  AbstractSql $sql
     * @param  mixed       $predicates
     * @param  ?string     $conjunction
     * @throws Exception
     */
    public function __construct(AbstractSql $sql, mixed $predicates = null, ?string $conjunction = null)
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
     * Set parameters
     *
     * @param  array $parameters
     * @return PredicateSet
     */
    public function setParameters(array $parameters): PredicateSet
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Add parameters
     *
     * @param  array $parameters
     * @return PredicateSet
     */
    public function addParameters(array $parameters): PredicateSet
    {
        foreach ($parameters as $name => $value) {
            $this->addParameter($name, $value);
        }
        return $this;
    }

    /**
     * Add parameter
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return PredicateSet
     */
    public function addParameter(mixed $name, mixed $value): PredicateSet
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get parameter
     *
     * @param  mixed $name
     * @return mixed
     */
    public function getParameter(mixed $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Has parameters
     *
     * @return bool
     */
    public function hasParameters(): bool
    {
        return !empty($this->parameters);
    }

    /**
     * Has parameter
     *
     * @param  mixed $name
     * @return bool
     */
    public function hasParameter(mixed $name): bool
    {
        return !empty($this->parameters[$name]);
    }

    /**
     * Extract values
     *
     * @param  bool $placeholder
     * @return array
     */
    public function extractValues(bool $placeholder = false): array
    {
        $values = [];

        foreach ($this->predicates as $i => $predicate) {
            if (($predicate instanceof EqualTo) && ($predicate->getConjunction() == 'AND')) {
                [$column, $value] = $predicate->getValues();
                if ((!$placeholder) && isset($this->parameters[$i])) {
                    $value = $this->parameters[$i];
                }
                $values[$column] = $value;
            }
        }

        return $values;
    }

    /**
     * Add a predicate from a string expression
     *
     * @param  string $expression
     * @param  mixed  $placeholder
     * @return PredicateSet
     */
    public function add(string $expression, mixed $placeholder = false): PredicateSet
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

        if ($placeholder !== false) {
            $this->addParameter($column, $value);
            if ($this->sql->isSqlite()) {
                $value = ':' . $column;
            } else if ($this->sql->isPgsql()) {
                $value = '$' . (int)$placeholder;
            } else {
                $value = $this->sql->getPlaceholder();
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
    public function addExpressions(array $expressions): PredicateSet
    {
        foreach ($expressions as $expression) {
            $this->add($expression);
        }

        return $this;
    }

    /**
     * Add an AND predicate from a string expression
     *
     * @param  ?string $expression
     * @throws Exception
     * @return PredicateSet
     */
    public function and(?string $expression = null): PredicateSet
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
     * @param  ?string $expression
     * @throws Exception
     * @return PredicateSet
     */
    public function or(?string $expression = null): PredicateSet
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
    public function equalTo(string $column, string $value): PredicateSet
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
    public function notEqualTo(string $column, string $value): PredicateSet
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
    public function greaterThan(string $column, string $value): PredicateSet
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
    public function greaterThanOrEqualTo(string $column, string $value): PredicateSet
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
    public function lessThan(string $column, string $value): PredicateSet
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
    public function lessThanOrEqualTo(string $column, string $value): PredicateSet
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
    public function like(string $column, string $value): PredicateSet
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
    public function notLike(string $column, string $value): PredicateSet
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
    public function between(string $column, string $value1, string $value2): PredicateSet
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
    public function notBetween(string $column, string $value1, string $value2): PredicateSet
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
    public function in(string $column, mixed $values): PredicateSet
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
    public function notIn(string $column, mixed $values): PredicateSet
    {
        return $this->addPredicate(new Predicate\NotIn([$column, $values], $this->nextConjunction));
    }

    /**
     * Predicate for IS NULL
     *
     * @param  string $column
     * @return PredicateSet
     */
    public function isNull(string $column): PredicateSet
    {
        return $this->addPredicate(new Predicate\IsNull($column, $this->nextConjunction));
    }

    /**
     * Predicate for IS NOT NULL
     *
     * @param  string $column
     * @return PredicateSet
     */
    public function isNotNull(string $column): PredicateSet
    {
        return $this->addPredicate(new Predicate\IsNotNull($column, $this->nextConjunction));
    }

    /**
     * Add AND predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @throws Predicate\Exception
     * @return PredicateSet
     */
    public function andPredicate(Predicate\AbstractPredicate $predicate): PredicateSet
    {
        $predicate->setConjunction('AND');
        return $this->addPredicate($predicate);
    }

    /**
     * Add OR predicate
     *
     * @param  Predicate\AbstractPredicate $predicate
     * @throws Predicate\Exception
     * @return PredicateSet
     */
    public function orPredicate(Predicate\AbstractPredicate $predicate): PredicateSet
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
    public function addPredicate(Predicate\AbstractPredicate $predicate): PredicateSet
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
    public function addPredicates(array $predicates): PredicateSet
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
    public function addPredicateSet(PredicateSet $predicateSet): PredicateSet
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
    public function addPredicateSets(array $predicateSets): PredicateSet
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
    public function nest(string $conjunction = 'AND'): PredicateSet
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
    public function andNest(): PredicateSet
    {
        return $this->nest('AND');
    }

    /**
     * Add a nested predicate set with the OR conjunction
     *
     * @return PredicateSet
     */
    public function orNest(): PredicateSet
    {
        return $this->nest('OR');
    }

    /**
     * Get the conjunction
     *
     * @param  string $conjunction
     * @throws Exception
     * @return PredicateSet
     */
    public function setConjunction(string $conjunction): PredicateSet
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
     * @return ?string
     */
    public function getConjunction(): ?string
    {
        return $this->conjunction;
    }

    /**
     * Get the next conjunction
     *
     * @param  string $conjunction
     * @throws Exception
     * @return PredicateSet
     */
    public function setNextConjunction(string $conjunction): PredicateSet
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
    public function getNextConjunction(): string
    {
        return $this->nextConjunction;
    }

    /**
     * Has predicates
     *
     * @return bool
     */
    public function hasPredicates(): bool
    {
        return (count($this->predicates) > 0);
    }

    /**
     * Get predicates
     *
     * @return array
     */
    public function getPredicates(): array
    {
        return $this->predicates;
    }

    /**
     * Has predicates
     *
     * @return bool
     */
    public function hasPredicateSets(): bool
    {
        return (count($this->predicateSets) > 0);
    }

    /**
     * Get predicates
     *
     * @return array
     */
    public function getPredicateSets(): array
    {
        return $this->predicateSets;
    }

    /**
     * Predicate set render method
     *
     * @throws Exception
     * @return string
     */
    public function render(): string
    {
        $predicateString = '';

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
    public function __toString(): string
    {
        return $this->render();
    }

}
