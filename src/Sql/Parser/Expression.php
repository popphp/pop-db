<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Parser;

use Pop\Db\Sql\AbstractSql;

/**
 * Predicate expression parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Expression
{

    /**
     * Allowed operators
     * @var array
     */
    protected static array $operators = [
        '>=', '<=', '!=', '=', '>', '<',
        'NOT LIKE', 'LIKE', 'NOT BETWEEN', 'BETWEEN',
        'NOT IN', 'IN', 'IS NOT NULL', 'IS NULL'
    ];

    /**
     * Method to parse a predicate string expression into its components
     *
     * @param  string $expression
     * @throws Exception
     * @return array
     */
    public static function parse(string $expression): array
    {
        $column   = null;
        $operator = null;
        $value    = null;

        // A predicate set renders its predicates wrapped in parentheses, so an expression can
        // come back in that form - unwrap it before looking for the column
        $expression   = self::unwrapExpression($expression);
        $columnEnd    = self::getColumnEnd($expression);
        $columnString = ($columnEnd !== false) ? substr($expression, 0, $columnEnd) : $expression;

        if (Keyword::indexOf($expression, ' NULL') !== false) {
            $column   = self::stripIdQuotes(trim($columnString));
            $operator = (Keyword::indexOf($expression, ' IS NOT NULL') !== false) ? 'IS NOT NULL' : 'IS NULL';
        } else if (Keyword::indexOf($expression, ' IN ') !== false) {
            $column   = self::stripIdQuotes(trim($columnString));
            $operator = (Keyword::indexOf($expression, ' NOT IN ') !== false) ? 'NOT IN' : 'IN';
            // Look for the value list after the column, so that a function call in the column
            // does not get mistaken for it
            $values   = substr($expression, (strpos($expression, '(', strlen($columnString)) + 1));
            $values   = substr($values, 0, strrpos($values, ')'));
            $values   = array_map(function($value) {
                return \Pop\Db\Sql\Parser\Expression::stripQuotes(trim($value));
            }, explode(',', $values));
            $value    = $values;
        } else if (Keyword::indexOf($expression, ' BETWEEN ') !== false) {
            $column   = self::stripIdQuotes(trim($columnString));
            $operator = (Keyword::indexOf($expression, ' NOT BETWEEN ') !== false) ? 'NOT BETWEEN' : 'BETWEEN';
            $value1   = substr($expression, (stripos($expression, 'BETWEEN ', strlen($columnString)) + 8));
            $value1   = trim(substr($value1, 0, stripos($value1, ' AND ')));
            $value2   = trim(substr($expression, (stripos($expression, ' AND ', strlen($columnString)) + 5)));
            $value    = '(' . self::stripQuotes($value1) . ' AND ' .  self::stripQuotes($value2) . ')';
        } else if (Keyword::indexOf($expression, ' LIKE ') !== false) {
            $column   = self::stripIdQuotes(trim($columnString));
            $operator = (Keyword::indexOf($expression, ' NOT LIKE ') !== false) ? 'NOT LIKE' : 'LIKE';
            $value    = self::stripQuotes(trim(substr($expression, (stripos($expression, ' LIKE ', strlen($columnString)) + 6))));
        } else {
            $column   = $columnString;
            $operator = ltrim(substr($expression, strlen($columnString)));
            $operatorEnd = strpos($operator, ' ');
            $operator = ($operatorEnd !== false) ? substr($operator, 0, $operatorEnd) : $operator;
            $value    = self::stripQuotes(
                trim(substr($expression, (strpos($expression, $operator, strlen($columnString)) + strlen($operator))))
            );
        }

        if (!in_array($operator, self::$operators)) {
            throw new Exception("Error: The operator '" . $operator . "' is not allowed.");
        }

        // A column shaped like a function call has to be one the SQL function whitelist accepts.
        // Anything else would be handed to quoteId() and come back as a bogus identifier such as
        // `SUM(id + 1)`, which errors on MySQL/PostgreSQL and silently reads as a text literal on
        // SQLite. AbstractSql::isSupportedFunctionCall() is deliberately strict and is not
        // loosened here, so an argument list carrying operators or comment markers is refused.
        if ((str_contains($column, '(')) && (!AbstractSql::isSupportedFunctionCall($column))) {
            throw new Exception(
                "Error: The column '" . $column . "' is not a valid column name or supported function call."
            );
        }

        return [
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value
        ];
    }

    /**
     * Strip one layer of enclosing parentheses from a predicate expression
     *
     * Only strips when the opening parenthesis is matched by the very last character, so that
     * '(id = 1)' is unwrapped while '(a = 1) AND (b = 2)' and 'COUNT(*) > 1' are left alone.
     *
     * @param  string $expression
     * @return string
     */
    protected static function unwrapExpression(string $expression): string
    {
        $expression = trim($expression);

        if ((!str_starts_with($expression, '(')) || (!str_ends_with($expression, ')'))) {
            return $expression;
        }

        $depth   = 0;
        $inQuote = null;
        $length  = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($inQuote !== null) {
                if ($char === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }

            if (($char === "'") || ($char === '"') || ($char === '`')) {
                $inQuote = $char;
            } else if ($char === '(') {
                $depth++;
            } else if ($char === ')') {
                $depth--;
                // The opening parenthesis closes before the end, so the expression is not
                // wrapped as a whole
                if ($depth === 0) {
                    return ($i === ($length - 1)) ? trim(substr($expression, 1, -1)) : $expression;
                }
            }
        }

        return $expression;
    }

    /**
     * Locate the end of the column at the start of a predicate expression
     *
     * The column runs up to the first whitespace that is not inside parentheses and not
     * inside a quoted string, so that the arguments of a function call are kept with the
     * column instead of being read as the operator - 'COUNT(DISTINCT id) > 1' has to yield
     * the column 'COUNT(DISTINCT id)', not 'COUNT(DISTINCT' and the operator 'id)'.
     *
     * @param  string $expression
     * @return int|false
     */
    protected static function getColumnEnd(string $expression): int|false
    {
        $depth   = 0;
        $inQuote = null;
        $length  = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($inQuote !== null) {
                if ($char === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }

            if (($char === "'") || ($char === '"') || ($char === '`')) {
                $inQuote = $char;
            } else if ($char === '(') {
                $depth++;
            } else if ($char === ')') {
                if ($depth > 0) {
                    $depth--;
                }
            } else if (($depth === 0) && (trim($char) === '')) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Method to parse predicate string expressions into its components
     *
     * @param  array $expressions
     * @return array
     */
    public static function parseExpressions(array $expressions): array
    {
        $components = [];

        foreach ($expressions as $expression) {
            $components[] = self::parse($expression);
        }

        return $components;
    }

    /**
     * Prepare a basic expression as a direct prepared predicate clause
     *
     * @param  string       $expression
     * @param  ?AbstractSql $sql
     * @param  bool         $withParams
     * @return array
     */
    public static function prepareExpression(
        string $expression, ?AbstractSql $sql = null, bool $withParams = true
    ): array
    {
        ['column' => $column, 'operator' => $operator, 'value' => $value] = self::parse($expression);

        $clause = $sql->quoteId($column) . ' ' . $operator;
        $params = [];

        if ($value !== null) {
            if ((stripos($operator, 'BETWEEN') !== false) && str_contains($value, 'AND')) {
                $value = array_map('trim', explode('AND', $value));
                if (count($value) == 2) {
                    if (str_starts_with($value[0], '(')) {
                        $value[0] = substr($value[0], 1);
                    }
                    if (str_ends_with($value[1], ')')) {
                        $value[1] = substr($value[1], 0, -1);
                    }
                }
            }
            if (is_array($value)) {
                if ((stripos($operator, 'BETWEEN') !== false) && (count($value) == 2)) {
                    if ($withParams) {
                        $clause .= ' ' . $sql->getPlaceholder() . ' AND ' . $sql->getPlaceholder();
                        $params[$column] = $value;
                    } else {
                        $clause .= ' ' . $sql->quote($value[0]) . ' AND ' . $sql->quote($value[1]);
                    }
                } else {
                    if ($withParams) {
                        $clause         .= ' (' . implode(', ', array_fill(0, count($value), $sql->getPlaceholder())) . ')';
                        $params[$column] = $value;
                    } else {
                        $quotedValues = [];
                        foreach ($value as $val) {
                            $quotedValues[] = $sql->quote($val);
                        }
                        $clause .= ' (' . implode(', ', $quotedValues) . ')';
                    }
                }
            } else {
                if ($withParams) {
                    $clause          .= ' ' . $sql->getPlaceholder();
                    $params[$column]  = $value;
                } else {
                    $clause .= ' ' . $sql->quote($value);
                }
            }
        }

        return ['clause' => $clause, 'params' => $params];
    }

    /**
     * Prepare basic expressions as direct prepared predicate clauses
     *
     * @param  array        $expressions
     * @param  ?AbstractSql $sql
     * @param  bool         $withParams
     * @param  bool         $flatten
     * @return array
     */
    public static function prepareExpressions(
        array $expressions, ?AbstractSql $sql = null, bool $withParams = true, bool $flatten = true
    ): array
    {
        $clauses = [];

        foreach ($expressions as $expression) {
            $clauses[] = self::prepareExpression($expression, $sql, $withParams);
        }

        if ($flatten) {
            $flattenClauses = [];
            $flattenParams  = [];
            $placeholder    = $sql->getPlaceholder();

            foreach ($clauses as $clause) {
                $flattenClauses[] = $clause['clause'];
                if (!empty($clause['params'])) {
                    if (is_array($clause['params'])) {
                        $i = 1;
                        foreach ($clause['params'] as $k => $v) {
                            if ($placeholder == ':') {
                                $flattenParams[$k . ($i++)] = $v;
                            } else {
                                $flattenParams[] = $v;
                            }
                        }
                    }
                }
            }

            return ['clauses' => $flattenClauses, 'params' => $flattenParams];
        } else {
            return $clauses;
        }
    }

    /**
     * Convert to expression to shorthand value
     *
     * @deprecated Produces the legacy shorthand format, which Condition::parseConditions()
     *             only accepts by triggering an E_USER_DEPRECATED notice. Use
     *             convertExpressionsToStructured() instead. Kept for callers still pinned to
     *             the legacy format; will be removed in pop-db v8.
     * @param  string $expression
     * @return array
     */
    public static function convertExpressionToShorthand(string $expression): array
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
                if (str_starts_with($value, '%')) {
                    $column = '%' . $column;
                    $value  = substr($value, 1);
                }
                if (str_ends_with($value, '%')) {
                    $column .= '%';
                    $value   = substr($value, 0, -1);
                }
                break;
            case 'NOT LIKE':
                if (str_starts_with($value, '%')) {
                    $column = '-%' . $column;
                    $value  = substr($value, 1);
                }
                if (str_ends_with($value, '%')) {
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

        return [$column => $value];
    }

    /**
     * Convert to expression to shorthand value
     *
     * @deprecated Produces the legacy shorthand format, which Condition::parseConditions()
     *             only accepts by triggering an E_USER_DEPRECATED notice. Use
     *             convertExpressionsToStructured() instead. Kept for callers still pinned to
     *             the legacy format; will be removed in pop-db v8.
     * @param  array $expressions
     * @return array
     */
    public static function convertExpressionsToShorthand(array $expressions): array
    {
        $conditions = [];

        foreach ($expressions as $expression) {
            $conditions = array_merge($conditions, self::convertExpressionToShorthand($expression));
        }

        return $conditions;
    }

    /**
     * Convert expressions into the structured operator-tuple format that
     * Sql\Parser\Condition::parseConditions() accepts natively - the modern counterpart to
     * convertExpressionsToShorthand(), which produces the legacy format that triggers
     * Condition::parseLegacy()'s deprecation notice.
     *
     * Unlike convertExpressionsToShorthand(), string keys are treated as already-prepared
     * column => value pairs and passed through untouched, an array entry is treated as an
     * already-structured condition and merged as-is, and a column produced by more than one
     * expression is routed into a nested 'AND' group instead of silently overwriting the
     * earlier condition - the collision legacy shorthand avoided only because the operator
     * was folded into the key (e.g. 'due_date>=' vs 'due_date<=').
     *
     * @param  array $expressions
     * @throws Exception
     * @return array
     */
    public static function convertExpressionsToStructured(array $expressions): array
    {
        $conditions = [];
        $repeated   = [];

        foreach ($expressions as $key => $expression) {
            // A string key is an already-prepared column => value pair rather than an
            // expression string - pass it through untouched.
            if (!is_int($key)) {
                $conditions[$key] = $expression;
                continue;
            }

            // Anything already in array form is a structured condition - merge it as-is.
            if (!is_string($expression)) {
                if (is_array($expression)) {
                    $conditions = array_merge($conditions, $expression);
                }
                continue;
            }

            ['column' => $column, 'operator' => $operator, 'value' => $value] = self::parse($expression);

            $operator = strtoupper($operator);

            $tuple = match ($operator) {
                'IS NULL', 'IS NOT NULL' => [$operator],
                'IN', 'NOT IN'           => [$operator, (array)$value],
                'BETWEEN', 'NOT BETWEEN' => array_merge([$operator], self::splitBetweenValues($value)),
                // A bare '=' against null keeps pop-db's documented IS NULL semantics rather
                // than rendering the never-true 'column = NULL'.
                '='                      => ($value === null) ? ['IS NULL'] : ['=', $value],
                default                  => [$operator, $value],
            };

            if (array_key_exists($column, $conditions)) {
                $repeated[] = [$column => $tuple];
            } else {
                $conditions[$column] = $tuple;
            }
        }

        if (!empty($repeated)) {
            $conditions['AND'] = $repeated;
        }

        return $conditions;
    }

    /**
     * Split a packed BETWEEN/NOT BETWEEN value - '(v1 AND v2)' - into its two operands
     *
     * Expression::parse() returns BETWEEN's operands packed into one string, not a pair, so
     * a straight array cast yields a one-value tuple that Condition::parseTuple() rejects
     * (BETWEEN has arity 2).
     *
     * @param  mixed $value
     * @return array
     */
    private static function splitBetweenValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        $value = trim((string)$value);

        while (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $value = trim(substr($value, 1, -1));
        }

        return array_map('trim', preg_split('/\s+AND\s+/i', $value, 2));
    }

    /**
     * Method to check if the column is shorthand
     *
     * @param  string $column
     * @return bool
     */
    public static function isShorthand(string $column): bool
    {
        return str_contains($column, '%') || str_ends_with($column, '-') || str_ends_with($column, '>=') ||
            str_ends_with($column, '<=') || str_ends_with($column, '!=') || str_ends_with($column, '>') ||
            str_ends_with($column, '<');
    }

    /**
     * Method to parse the shorthand columns to create expressions and their parameters
     *
     * @param  array   $columns
     * @param  ?string $placeholder
     * @param  bool    $flatten
     * @return array
     */
    public static function parseShorthand(array $columns, ?string $placeholder = null, bool $flatten = true): array
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
            if ($value === null) {
                $expressions = self::applyExpression(
                    $expressions, $parsedColumn, $placeholder, self::buildNullExpression($parsedColumn, $operator)
                );
            // IN/NOT IN
            } else if (is_array($value)) {
                [$newExpression, $paramValue] = self::buildInExpression(
                    $parsedColumn, $operator, $value, $placeholder, $pHolder, $i
                );
                $expressions = self::applyExpression($expressions, $parsedColumn, $placeholder, $newExpression);
                $params      = self::applyParam($params, $parsedColumn, $placeholder, $j, $paramValue);
            // BETWEEN/NOT BETWEEN
            } else if (is_string($value) && str_starts_with($value, '(') && str_ends_with($value, ')')) {
                [$newExpression, $paramValue] = self::buildBetweenExpression(
                    $parsedColumn, $operator, $value, $placeholder, $pHolder, $i
                );
                $expressions = self::applyExpression($expressions, $parsedColumn, $placeholder, $newExpression);
                $params      = self::applyParam($params, $parsedColumn, $placeholder, $j, $paramValue);
            // LIKE/NOT LIKE or Standard Operators
            } else {
                [$newExpression, $paramValue] = self::buildComparisonExpression(
                    $column, $parsedColumn, $operator, $value, $placeholder, $pHolder, $i
                );
                $expressions = self::applyExpression($expressions, $parsedColumn, $placeholder, $newExpression);
                $params      = self::applyParam($params, $parsedColumn, $placeholder, $j, $paramValue);
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
     * Add a rendered expression fragment to the expressions array, keyed by column name when
     * using named (':') placeholders, or appended positionally otherwise
     *
     * @param  array   $expressions
     * @param  string  $parsedColumn
     * @param  ?string $placeholder
     * @param  string  $newExpression
     * @return array
     */
    private static function applyExpression(array $expressions, string $parsedColumn, ?string $placeholder, string $newExpression): array
    {
        if ($placeholder == ':') {
            $expressions[$parsedColumn] = $newExpression;
        } else {
            $expressions[] = $newExpression;
        }

        return $expressions;
    }

    /**
     * Add a bound parameter value to the params array, keyed by column name when using named
     * (':') placeholders, or by the running column index otherwise
     *
     * @param  array   $params
     * @param  string  $parsedColumn
     * @param  ?string $placeholder
     * @param  int     $j
     * @param  mixed   $paramValue
     * @return array
     */
    private static function applyParam(array $params, string $parsedColumn, ?string $placeholder, int $j, mixed $paramValue): array
    {
        if ($placeholder == ':') {
            $params[$parsedColumn] = $paramValue;
        } else {
            $params[$j] = $paramValue;
        }

        return $params;
    }

    /**
     * Build an IS NULL/IS NOT NULL expression fragment
     *
     * @param  string $parsedColumn
     * @param  string $operator
     * @return string
     */
    private static function buildNullExpression(string $parsedColumn, string $operator): string
    {
        return $parsedColumn . ' IS ' . (($operator == 'NOT') ? 'NOT ' : '') . 'NULL';
    }

    /**
     * Build an IN/NOT IN expression fragment and its bound parameter value(s)
     *
     * @param  string  $parsedColumn
     * @param  string  $operator
     * @param  array   $value
     * @param  ?string $placeholder
     * @param  ?string $pHolder
     * @param  int     $i
     * @return array [string $newExpression, array $paramValue]
     */
    private static function buildInExpression(
        string $parsedColumn, string $operator, array $value, ?string $placeholder, ?string $pHolder, int &$i
    ): array
    {
        $p = [];

        if ($placeholder == ':') {
            $pHolders = [];
            foreach ($value as $k => $val) {
                $pHolders[] = $pHolder . ($k + 1);
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

        if ($placeholder !== null) {
            $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') . 'IN (' .
                implode(', ', $pHolders) . ')';
        } else {
            $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') . 'IN (' .
                implode(', ', array_map('Pop\Db\Sql\Parser\Expression::quote', $value)) . ')';
        }

        return [$newExpression, $p];
    }

    /**
     * Build a BETWEEN/NOT BETWEEN expression fragment and its two bound boundary values
     *
     * @param  string  $parsedColumn
     * @param  string  $operator
     * @param  string  $value
     * @param  ?string $placeholder
     * @param  ?string $pHolder
     * @param  int     $i
     * @return array [string $newExpression, array $paramValue]
     */
    private static function buildBetweenExpression(
        string $parsedColumn, string $operator, string $value, ?string $placeholder, ?string $pHolder, int &$i
    ): array
    {
        $values            = substr($value, (strpos($value, '(') + 1));
        $values            = substr($values, 0, strpos($values, ')'));
        $delimiter         = (str_contains($values, ',')) ? ',' : 'AND';
        [$value1, $value2] = array_map('trim', explode($delimiter, $values));

        $p = [$value1, $value2];

        if ($placeholder == ':') {
            $pHolder2 = $pHolder . 2;
            $pHolder .= 1;
        } else if ($placeholder == '$') {
            $pHolder2 = $placeholder . ++$i;
        } else {
            $pHolder2 = $pHolder;
        }

        if ($placeholder !== null) {
            $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') .
                'BETWEEN ' . $pHolder . ' AND ' . $pHolder2;
        } else {
            $newExpression = $parsedColumn . (($operator == 'NOT') ? ' NOT ' : ' ') .
                'BETWEEN ' . self::quote($value1) . ' AND ' . self::quote($value2);
        }

        $i++;

        return [$newExpression, $p];
    }

    /**
     * Build a LIKE/NOT LIKE or standard comparison-operator expression fragment and its bound value
     *
     * @param  string  $column
     * @param  string  $parsedColumn
     * @param  string  $operator
     * @param  mixed   $value
     * @param  ?string $placeholder
     * @param  ?string $pHolder
     * @param  int     $i
     * @return array [string $newExpression, mixed $paramValue]
     */
    private static function buildComparisonExpression(
        string $column, string $parsedColumn, string $operator, mixed $value, ?string $placeholder, ?string $pHolder, int &$i
    ): array
    {
        if ((str_starts_with($column, '%')) || (str_starts_with($column, '-%'))) {
            $value = '%' . $value;
        }
        if ((str_ends_with($column, '%')) || (str_ends_with($column, '%-'))) {
            $value .= '%';
        }

        if ($placeholder !== null) {
            $newExpression = $parsedColumn . ' ' . $operator . ' ' . $pHolder;
        } else {
            $newExpression = $parsedColumn . ' ' . $operator . ' ' . self::quote($value);
        }

        $i++;

        return [$newExpression, $value];
    }

    /**
     * Strip ID quotes
     *
     * @param  string $identifier
     * @return string
     */
    public static function stripIdQuotes(string $identifier): string
    {
        if (((str_starts_with($identifier, '"')) && (str_ends_with($identifier, '"'))) ||
            ((str_starts_with($identifier, '`')) && (str_ends_with($identifier, '`'))) ||
            ((str_starts_with($identifier, '[')) && (str_ends_with($identifier, ']')))) {
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
    public static function stripQuotes(string $value): string
    {
        if (((str_starts_with($value, '"')) && (str_ends_with($value, '"'))) ||
            ((str_starts_with($value, "'")) && (str_ends_with($value, "'")))) {
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
    public static function quote(string $value): string
    {
        if (($value == '') ||
            (($value != '?') && (!str_starts_with($value, ':')) && (preg_match('/^\$\d*\d$/', $value) == 0) &&
                (preg_match('/^\d*$/', $value) == 0))) {
            $value = "'" . $value . "'";
        }
        return $value;
    }

}
