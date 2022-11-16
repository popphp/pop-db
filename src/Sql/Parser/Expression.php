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
namespace Pop\Db\Sql\Parser;

/**
 * Predicate expression parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
class Expression
{

    /**
     * Allowed operators
     * @var array
     */
    protected static $operators = [
        '>=', '<=', '!=', '=', '>', '<',
        'NOT LIKE', 'LIKE', 'NOT BETWEEN', 'BETWEEN',
        'NOT IN', 'IN', 'IS NOT NULL', 'IS NULL'
    ];

    /**
     * Method to parse a predicate string expression into its components
     *
     * @param  string $expression
     * @return array
     */
    public static function parse($expression)
    {
        $column   = null;
        $operator = null;
        $value    = null;

        if (stripos($expression, ' NULL') !== false) {
            $column   = self::stripIdQuotes(trim(substr($expression, 0, strpos($expression, ' '))));
            $operator = (stripos($expression, ' IS NOT NULL') !== false) ? 'IS NOT NULL' : 'IS NULL';
        } else if (stripos($expression, ' IN ') !== false) {
            $column   = self::stripIdQuotes(trim(substr($expression, 0, strpos($expression, ' '))));
            $operator = (stripos($expression, ' NOT IN ') !== false) ? 'NOT IN' : 'IN';
            $values   = substr($expression, (strpos($expression, '(') + 1));
            $values   = substr($values, 0, strpos($values, ')'));
            $values   = array_map(function($value) {
                return \Pop\Db\Sql\Parser\Expression::stripQuotes(trim($value));
            }, explode(',', $values));
            $value    = $values;
        } else if (stripos($expression, ' BETWEEN ') !== false) {
            $column   = self::stripIdQuotes(trim(substr($expression, 0, strpos($expression, ' '))));
            $operator = (stripos($expression, ' NOT BETWEEN ') !== false) ? 'NOT BETWEEN' : 'BETWEEN';
            $value1   = substr($expression, (strpos($expression, 'BETWEEN ') + 8));
            $value1   = trim(substr($value1, 0, strpos($value1, ' ')));
            $value2   = trim(substr($expression, (stripos($expression, ' AND ') + 5)));
            $value    = [self::stripQuotes($value1), self::stripQuotes($value2)];
        } else if (stripos($expression, ' LIKE ') !== false) {
            $column   = self::stripIdQuotes(trim(substr($expression, 0, strpos($expression, ' '))));
            $operator = (stripos($expression, ' NOT LIKE ') !== false) ? 'NOT LIKE' : 'LIKE';
            $value    = self::stripQuotes(trim(substr($expression, (stripos($expression, ' LIKE ') + 6))));
        } else {
            $column   = substr($expression, 0, strpos($expression, ' '));
            $operator = substr($expression, (strlen($column) + 1));
            $operator = substr($operator, 0, strpos($operator, ' '));
            $value    = self::stripQuotes(trim(substr($expression, (strpos($expression, $operator) + strlen($operator)))));
        }

        if (!in_array($operator, self::$operators)) {
            throw new Exception("Error: The operator '" . $operator . "' is not allowed.");
        }

        return [
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value
        ];
    }

    /**
     * Method to parse predicate string expressions into its components
     *
     * @param  array $expressions
     * @return array
     */
    public static function parseExpressions(array $expressions)
    {
        $components = [];

        foreach ($expressions as $expression) {
            $components[] = self::parse($expression);
        }

        return $components;
    }

    /**
     * Convert to expression to shorthand value
     *
     * @param  string $expression
     * @return array
     */
    public static function convertExpressionToShorthand($expression)
    {
        ['column' => $column, 'operator' => $operator, 'value' => $value] = self::parse($expression);

        switch ($operator) {
            case '>=':
            case '<=':
            case '!=':
            case '>':
            case '<':
                $column .= $operator;
                break;
            case 'LIKE':
                if (substr($value, 0, 1) == '%') {
                    $column = '%' . $column;
                    $value  = substr($value, 1);
                }
                if (substr($value, -1) == '%') {
                    $column .= '%';
                    $value   = substr($value, 0, -1);
                }
                break;
            case 'NOT LIKE':
                if (substr($value, 0, 1) == '%') {
                    $column = '-%' . $column;
                    $value  = substr($value, 1);
                }
                if (substr($value, -1) == '%') {
                    $column .= '%-';
                    $value   = substr($value, 0, -1);
                }
                break;
            case 'NOT IN':
            case 'NOT BETWEEN':
            case 'IS NOT NULL':
                $column .= '-';
                break;
        }

        if (strpos($expression, ' BETWEEN ') !== false) {
            $value = '(' . implode(', ', $value) . ')';
        }

        return [$column => $value];
    }

    /**
     * Convert to expression to shorthand value
     *
     * @param  array $expressions
     * @return array
     */
    public static function convertExpressionsToShorthand(array $expressions)
    {
        $conditions = [];

        foreach ($expressions as $expression) {
            $conditions = array_merge($conditions, self::convertExpressionToShorthand($expression));
        }

        return $conditions;
    }

    /**
     * Method to parse the shorthand columns to create expressions and their parameters
     *
     * @param  array   $columns
     * @param  string  $placeholder
     * @param  boolean $flatten
     * @return array
     */
    public static function parseShorthand($columns, $placeholder = null, $flatten = true)
    {
        $expressions = [];
        $params      = [];
        $i           = 1;
        $j           = 0;

        foreach ($columns as $column => $value) {
            ['column' => $parsedColumn, 'operator' => $operator] = Operator::parse($column);

            $pHolder = $placeholder;
            if ($placeholder == ':') {
                $pHolder .= $parsedColumn;
            } else if ($placeholder == '$') {
                $pHolder .= $i;
            }

            // IS NULL/IS NOT NULL
            if (null === $value) {
                $newExpression = $parsedColumn . ' IS ' . (($operator == 'NOT') ? 'NOT ' : '') . 'NULL';
                if ($placeholder == ':') {
                    $expressions[$parsedColumn] = $newExpression;
                } else {
                    $expressions[] = $newExpression;
                }
            // IN/NOT IN
            } else if (is_array($value)) {
                $p = [];
                if ($placeholder == ':') {
                    $pHolders = [];
                    foreach ($value as $j => $val) {
                        $ph         = $pHolder . ($j + 1);
                        $pHolders[] = $ph;
                        $p[]        = $val;
                    }
                } else if ($placeholder == '$') {
                    $pHolders = [];
                    foreach ($value as $val) {
                        $pHolders[] = $placeholder . $i++;
                        $p[]        = $val;
                    }
                } else {
                    $pHolders = array_fill(0, count($value), $pHolder);
                    $p        = $value;
                    $i++;
                }
                if (null !== $placeholder) {
                    $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') . 'IN (' .
                        implode(', ', $pHolders) . ')';
                    if ($placeholder == ':') {
                        $expressions[$parsedColumn] = $newExpression;
                    } else {
                        $expressions[] = $newExpression;
                    }
                } else {
                    $expressions[] = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') . 'IN (' .
                        implode(', ', array_map('Pop\Db\Sql\Parser\Expression::quote', $value)) . ')';
                }
                if ($placeholder == ':') {
                    $params[$parsedColumn] = $p;
                } else {
                    $params[$j] = $p;
                }
            // BETWEEN/NOT BETWEEN
            } else if (is_string($value) && (substr($value, 0, 1) == '(') && (substr($value, -1) == ')') &&
                (strpos($value, ',') !== false)) {
                $values            = substr($value, (strpos($value, '(') + 1));
                $values            = substr($values, 0, strpos($values, ')'));
                [$value1, $value2] = array_map('trim', explode(',', $values));
                $p                 = [$value1, $value2];

                if ($placeholder == ':') {
                    $pHolder2 = $pHolder . 2;
                    $pHolder .= 1;
                } else if ($placeholder == '$') {
                    $pHolder2 = $placeholder . ++$i;
                } else {
                    $pHolder2 = $pHolder;
                }

                if (null !== $placeholder) {
                    $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') .
                        'BETWEEN ' . $pHolder . ' AND ' . $pHolder2;
                    if ($placeholder == ':') {
                        $expressions[$parsedColumn] = $newExpression;
                    } else {
                        $expressions[] = $newExpression;
                    }
                } else {
                    $expressions[] = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') .
                        'BETWEEN ' . self::quote($value1) . ' AND ' . self::quote($value2);
                }
                if ($placeholder == ':') {
                    $params[$parsedColumn] = $p;
                } else {
                    $params[$j] = $p;
                }
                $i++;
            // LIKE/NOT LIKE or Standard Operators
            } else  {
                if ((substr($column, 0, 1) == '%') || (substr($column, 0, 2) == '-%')) {
                    $value  = '%' . $value;
                }
                if ((substr($column, -1) == '%') || (substr($column, -2) == '%-')) {
                    $value .= '%';
                }
                if (null !== $placeholder) {
                    $newExpression = $parsedColumn . ' ' . $operator . ' ' . $pHolder;
                    if ($placeholder == ':') {
                        $expressions[$parsedColumn] = $newExpression;
                    } else {
                        $expressions[] = $newExpression;
                    }
                } else {
                    $expressions[] = $parsedColumn . ' ' . $operator . ' ' . self::quote($value);
                }
                if ($placeholder == ':') {
                    $params[$parsedColumn] = $value;
                } else {
                    $params[$j] = $value;
                }
                $i++;
            }
            $j++;
        }

        if ($flatten) {
            $flattenParams = [];

            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($placeholder == ':') {
                            $flattenParams[$key . ($k + 1)] = $v;
                        } else {
                            $flattenParams[] = $v;
                        }
                    }
                } else {
                    if ($placeholder == ':') {
                        $flattenParams[$key] = $value;
                    } else {
                        $flattenParams[] = $value;
                    }
                }
            }

            return ['expressions' => $expressions, 'params' => $flattenParams];
        } else {
            return ['expressions' => $expressions, 'params' => $params];
        }

    }

    /**
     * Strip ID quotes
     *
     * @param  string $identifier
     * @return string
     */
    public static function stripIdQuotes($identifier)
    {
        if (((substr($identifier, 0, 1) == '"') && (substr($identifier, -1) == '"')) ||
            ((substr($identifier, 0, 1) == '`') && (substr($identifier, -1) == '`')) ||
            ((substr($identifier, 0, 1) == '[') && (substr($identifier, -1) == ']'))) {
            $identifier = substr($identifier, 1);
            $identifier = substr($identifier, 0, -1);
        }

        return $identifier;
    }

    /**
     * Strip quotes
     *
     * @param  string $value
     * @return string
     */
    public static function stripQuotes($value)
    {
        if (((substr($value, 0, 1) == '"') && (substr($value, -1) == '"')) ||
            ((substr($value, 0, 1) == "'") && (substr($value, -1) == "'"))) {
            $value = substr($value, 1);
            $value = substr($value, 0, -1);
        }

        return $value;
    }

    /**
     * Quote the value (if it is not a numeric value)
     *
     * @param  string $value
     * @return string
     */
    public static function quote($value)
    {
        if (($value == '') ||
            (($value != '?') && (substr($value, 0, 1) != ':') && (preg_match('/^\$\d*\d$/', $value) == 0) &&
                !is_int($value) && !is_float($value) && (preg_match('/^\d*$/', $value) == 0))) {
            $value = "'" . $value . "'";
        }
        return $value;
    }

}