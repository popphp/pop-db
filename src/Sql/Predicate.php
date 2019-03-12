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

use Pop\Db\Parser;

/**
 * Predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Predicate
{

    /**
     * SQL object
     * @var AbstractSql
     */
    protected $sql = null;

    /**
     * Predicates array
     * @var array
     */
    protected $predicates = [];

    /**
     * Nested predicates
     * @var array
     */
    protected $nested = [];

    /**
     * Nested group combine
     * @var string
     */
    protected $nestedCombine = 'AND';

    /**
     * Flag to determine if the predicate is nested
     * @var boolean
     */
    protected $isNested = false;

    /**
     * Constructor
     *
     * Instantiate the predicate collection object.
     *
     * @param  AbstractSql $sql
     * @param  boolean     $nested
     */
    public function __construct(AbstractSql $sql, $nested = false)
    {
        $this->sql      = $sql;
        $this->isNested = (bool)$nested;
    }

    /**
     * Add a nested predicate
     *
     * @return Predicate
     */
    public function nest()
    {
        $this->nested[] = new Predicate($this->sql, true);
        return $this->nested[count($this->nested) - 1];
    }

    /**
     * Add a nested predicate with AND
     *
     * @return Predicate
     */
    public function andNest()
    {
        if (count($this->nested) > 0) {
            $this->nested[(count($this->nested) - 1)]->setNestedCombine('AND');
        }
        return $this->nest();
    }

    /**
     * Add a nested predicate with OR
     *
     * @return Predicate
     */
    public function orNest()
    {
        if (count($this->nested) > 0) {
            $this->nested[(count($this->nested) - 1)]->setNestedCombine('OR');
        }
        return $this->nest();
    }

    /**
     * Determine if it has a nested predicate branch
     *
     * @param  int $i
     * @return boolean
     */
    public function hasNest($i = null)
    {
        return (null === $i) ? (count($this->nested) > 0) : (isset($this->nested[$i]));
    }

    /**
     * Get a nested predicate
     *
     * @param  int $i
     * @return mixed
     */
    public function getNest($i)
    {
        return (isset($this->nested[$i])) ? $this->nested[$i] : null;
    }

    /**
     * Determine if predicate is nested
     *
     * @return boolean
     */
    public function isNested()
    {
        return $this->isNested;
    }

    /**
     * Get nested combine
     *
     * @param  string $combine
     * @return Predicate
     */
    public function setNestedCombine($combine)
    {
        $this->nestedCombine = $combine;
        return $this;
    }

    /**
     * Get nested combine
     *
     * @return string
     */
    public function getNestedCombine()
    {
        return $this->nestedCombine;
    }

    /**
     * Add a predicate from a string
     *
     * @param  mixed $predicate
     * @return Predicate
     */
    public function add($predicate)
    {
        $predicates = [];

        // If the predicate is a string
        if (is_string($predicate)) {
            $predicates = [Parser\Predicate::parse($predicate)];
        // If the predicate is an array of strings
        } else if (is_array($predicate) && isset($predicate[0]) && is_string($predicate[0])) {
            foreach ($predicate as $pred) {
                $predicates[] = Parser\Predicate::parse($pred);
            }
        // If the predicate is an array of associative array values, i.e., [['id' => 1], ...]
        } else if (is_array($predicate) && isset($predicate[0]) && is_array($predicate[0])) {
            foreach ($predicate as $pred) {
                $key = current(array_keys($pred));
                if (is_string($key) && !is_numeric($key)) {
                    $val          = $pred[$key];
                    $predicates[] = [$key, '=', $val];
                }
            }
        // If the predicate is a single associative array, i.e., ['id' => 1]
        } else {
            $key = current(array_keys($predicate));
            if (is_string($key) && !is_numeric($key)) {
                $val          = $predicate[$key];
                $predicates[] = [$key, '=', $val];
            }
        }

        // Loop through and add the predicates
        foreach ($predicates as $predicate) {
            if (count($predicate) >= 2) {
                switch ($predicate[1]) {
                    case '>=':
                        $this->greaterThanOrEqualTo($predicate[0], $predicate[2]);
                        break;
                    case '<=':
                        $this->lessThanOrEqualTo($predicate[0], $predicate[2]);
                        break;
                    case '!=':
                        $this->notEqualTo($predicate[0], $predicate[2]);
                        break;
                    case '=':
                        $this->equalTo($predicate[0], $predicate[2]);
                        break;
                    case '>':
                        $this->greaterThan($predicate[0], $predicate[2]);
                        break;
                    case '<':
                        $this->lessThan($predicate[0], $predicate[2]);
                        break;
                    case 'NOT LIKE':
                        $this->notLike($predicate[0], $predicate[2]);
                        break;
                    case 'LIKE':
                        $this->like($predicate[0], $predicate[2]);
                        break;
                    case 'NOT BETWEEN':
                        $this->notBetween($predicate[0], $predicate[2][0], $predicate[2][1]);
                        break;
                    case 'BETWEEN':
                        $this->between($predicate[0], $predicate[2][0], $predicate[2][1]);
                        break;
                    case 'NOT IN':
                        $this->notIn($predicate[0], $predicate[2]);
                        break;
                    case 'IN':
                        $this->in($predicate[0], $predicate[2]);
                        break;
                    case 'IS NOT NULL':
                        $this->isNotNull($predicate[0]);
                        break;
                    case 'IS NULL':
                        $this->isNull($predicate[0]);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Determine if there are predicates
     *
     * @return boolean
     */
    public function hasPredicates()
    {
        return (count($this->predicates) > 0);
    }

    /**
     * Get last predicate set added
     *
     * @return Predicate\AbstractPredicateSet
     */
    public function getLastPredicateSet()
    {
        return (count($this->predicates) > 0) ?
            $this->predicates[(count($this->predicates) - 1)] : null;
    }

    /**
     * Predicate for =
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function equalTo($column, $value)
    {
        $this->predicates[] = new Predicate\EqualTo([$column, $value]);
        return $this;
    }

    /**
     * Predicate for !=
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function notEqualTo($column, $value)
    {
        $this->predicates[] = new Predicate\NotEqualTo([$column, $value]);
        return $this;
    }

    /**
     * Predicate for >
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function greaterThan($column, $value)
    {
        $this->predicates[] = new Predicate\GreaterThan([$column, $value]);
        return $this;
    }

    /**
     * Predicate for >=
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function greaterThanOrEqualTo($column, $value)
    {
        $this->predicates[] = new Predicate\GreaterThanOrEqualTo([$column, $value]);
        return $this;
    }

    /**
     * Predicate for <
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function lessThan($column, $value)
    {
        $this->predicates[] = new Predicate\LessThan([$column, $value]);
        return $this;
    }

    /**
     * Predicate for <=
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function lessThanOrEqualTo($column, $value)
    {
        $this->predicates[] = new Predicate\LessThanOrEqualTo([$column, $value]);
        return $this;
    }

    /**
     * Predicate for LIKE
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function like($column, $value)
    {
        $this->predicates[] = new Predicate\Like([$column, $value]);
        return $this;
    }

    /**
     * Predicate for NOT LIKE
     *
     * @param  string $column
     * @param  string $value
     * @return Predicate
     */
    public function notLike($column, $value)
    {
        $this->predicates[] = new Predicate\NotLike([$column, $value]);
        return $this;
    }

    /**
     * Predicate for BETWEEN
     *
     * @param  string $column
     * @param  string $value1
     * @param  string $value2
     * @return Predicate
     */
    public function between($column, $value1, $value2)
    {
        $this->predicates[] = new Predicate\Between([$column, $value1, $value2]);
        return $this;
    }

    /**
     * Predicate for NOT BETWEEN
     *
     * @param  string $column
     * @param  string $value1
     * @param  string $value2
     * @return Predicate
     */
    public function notBetween($column, $value1, $value2)
    {
        $this->predicates[] = new Predicate\NotBetween([$column, $value1, $value2]);
        return $this;
    }

    /**
     * Predicate for IN
     *
     * @param  string $column
     * @param  mixed  $values
     * @return Predicate
     */
    public function in($column, $values)
    {
        $this->predicates[] = new Predicate\In([$column, $values]);
        return $this;
    }

    /**
     * Predicate for NOT IN
     *
     * @param  string $column
     * @param  mixed  $values
     * @return Predicate
     */
    public function notIn($column, $values)
    {
        $this->predicates[] = new Predicate\NotIn([$column, $values]);
        return $this;
    }

    /**
     * Predicate for IS NULL
     *
     * @param  string $column
     * @return Predicate
     */
    public function isNull($column)
    {
        $this->predicates[] = new Predicate\IsNull([$column]);
        return $this;
    }

    /**
     * Predicate for IS NOT NULL
     *
     * @param  string $column
     * @return Predicate
     */
    public function isNotNull($column)
    {
        $this->predicates[] = new Predicate\IsNotNull([$column]);
        return $this;
    }

    /**
     * Predicate render method
     *
     * @param  int $count
     * @return string
     */
    public function render($count = 1)
    {
        $predicateString = null;

        // Loop through the nested predicated
        if (count($this->nested) > 0) {
            foreach ($this->nested as $key => $nested) {
                $curPredicate = (string)$nested;
                if ($key == 0) {
                    $predicateString .= $curPredicate;
                } else {
                    $predicateString .= ' ' . $this->nested[($key - 1)]->getNestedCombine() . ' ' . $curPredicate;
                }
            }
            $predicateString = '(' . $predicateString . ')';
        }

        // Loop through and format the predicates
        if (count($this->predicates) > 0) {
            if (null !== $predicateString) {
                $predicateString .= ' ' . $this->nested[(count($this->nested) - 1)]->getNestedCombine() . ' ';
            }

            $paramCount = $count;
            $dbType     = $this->sql->getDbType();

            foreach ($this->predicates as $key => $predicate) {
                $format       = $predicate->getFormat();
                $values       = $predicate->getValues();
                $curPredicate = '(';
                for ($i = 0; $i < count($values); $i++) {
                    if ($i == 0) {
                        $format = str_replace('%1', $this->sql->quoteId($values[$i]), $format);
                    } else {
                        if (is_array($values[$i])) {
                            $vals = $values[$i];
                            foreach ($vals as $k => $v) {
                                $predValue = (strpos($values[0], '.') !== false) ?
                                    substr($values[0], (strpos($values[0], '.') + 1)) : $values[0];

                                // Check for named parameters
                                if ((':' . $predValue == substr($v, 0, strlen(':' . $predValue))) &&
                                    ($dbType !== AbstractSql::SQLITE)) {
                                    if (($dbType == AbstractSql::MYSQL) || ($dbType == AbstractSql::SQLSRV)) {
                                        $v = '?';
                                    } else if (($dbType == AbstractSql::PGSQL) &&
                                        (!($this->sql->db() instanceof \Pop\Db\Adapter\Pdo))) {
                                        $v = '$' . $paramCount;
                                        $paramCount++;
                                    }
                                }
                                $vals[$k] = (null === $v) ? 'NULL' : $this->sql->quote($v);
                            }
                            $format = str_replace('%' . ($i + 1), implode(', ', $vals), $format);
                        } else {
                            if ($values[$i] instanceof \Pop\Db\Sql\AbstractSql) {
                                $val = (string)$values[$i];
                            } else {
                                $val = (null === $values[$i]) ? 'NULL' :
                                    $this->sql->quote($values[$i]);
                            }

                            $predValue = (strpos($values[0], '.') !== false) ?
                                substr($values[0], (strpos($values[0], '.') + 1)) : $values[0];

                            // Check for named parameters
                            if ((':' . $predValue == substr($val, 0, strlen(':' . $predValue))) &&
                                ($dbType !== AbstractSql::SQLITE)) {
                                if (($dbType == AbstractSql::MYSQL) || ($dbType == AbstractSql::SQLSRV)) {
                                    $val = '?';
                                } else if (($dbType == AbstractSql::PGSQL) &&
                                    (!($this->sql->db() instanceof \Pop\Db\Adapter\Pdo))) {
                                    $val = '$' . $paramCount;
                                    $paramCount++;
                                }
                            }
                            $format = str_replace('%' . ($i + 1), $val, $format);
                        }
                    }
                }

                $curPredicate .= $format . ')';

                if ($key == 0) {
                    $predicateString .= $curPredicate;
                } else {
                    $predicateString .= ' ' . $this->predicates[($key - 1)]->getCombine() . ' ' . $curPredicate;
                }
            }
        }

        return $predicateString;
    }

    /**
     * Return predicate string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}