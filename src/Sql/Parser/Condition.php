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
namespace Pop\Db\Sql\Parser;

use Pop\Db\Sql\AbstractSql;
use Pop\Db\Sql\PredicateSet;

/**
 * Structured shorthand condition parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Condition
{

    /**
     * Supported operators, mapped to their PredicateSet method and value arity
     * @var array
     */
    protected const OPERATORS = [
        '='           => ['method' => 'equalTo',              'arity' => 1],
        '!='          => ['method' => 'notEqualTo',           'arity' => 1],
        '>'           => ['method' => 'greaterThan',          'arity' => 1],
        '>='          => ['method' => 'greaterThanOrEqualTo', 'arity' => 1],
        '<'           => ['method' => 'lessThan',             'arity' => 1],
        '<='          => ['method' => 'lessThanOrEqualTo',    'arity' => 1],
        'LIKE'        => ['method' => 'like',                 'arity' => 1],
        'NOT LIKE'    => ['method' => 'notLike',              'arity' => 1],
        'IN'          => ['method' => 'in',                   'multi' => true],
        'NOT IN'      => ['method' => 'notIn',                'multi' => true],
        'BETWEEN'     => ['method' => 'between',              'arity' => 2],
        'NOT BETWEEN' => ['method' => 'notBetween',           'arity' => 2],
        'IS NULL'     => ['method' => 'isNull',               'arity' => 0],
        'IS NOT NULL' => ['method' => 'isNotNull',            'arity' => 0],
        'CONTAINS'    => ['method' => 'jsonContains',         'arity' => 1],
    ];

    /**
     * Parse a shorthand columns array into a PredicateSet
     *
     * @param  array       $columns
     * @param  AbstractSql $sql
     * @param  bool        $allowLegacy
     * @throws Exception
     * @return PredicateSet
     */
    public static function parse(array $columns, AbstractSql $sql, bool $allowLegacy = true): PredicateSet
    {
        // Bound-parameter keys must be unique across the WHOLE parse tree (every nested OR/AND
        // group included), because PredicateSet::getParameters() merges nested sets with
        // array_merge(), which silently drops duplicate string keys. This counter is threaded
        // by reference through every recursive call so that a column repeated across branches
        // (e.g. 'logins' in two OR branches) still gets two distinct parameter keys.
        //
        // It is deliberately NOT AbstractSql::$parameterCount - that counter is owned by
        // AbstractSql and reserved for the PostgreSQL "$N" token-numbering rewrite in
        // AbstractSql::getParameter(). The two concerns both need "a counter", but not the
        // same one.
        $parameterIndex = 0;

        return self::parseConditions($columns, $sql, $allowLegacy, $parameterIndex);
    }

    /**
     * Parse a shorthand columns array into a PredicateSet, threading the tree-wide
     * parameter-key counter through each level of recursion
     *
     * @param  array       $columns
     * @param  AbstractSql $sql
     * @param  bool        $allowLegacy
     * @param  int         $parameterIndex
     * @throws Exception
     * @return PredicateSet
     */
    protected static function parseConditions(
        array $columns, AbstractSql $sql, bool $allowLegacy, int &$parameterIndex
    ): PredicateSet
    {
        $predicateSet  = new PredicateSet($sql);
        $legacyColumns = [];
        $newColumns    = [];
        $groups        = [];
        $existsKeys    = [];

        foreach ($columns as $key => $value) {
            if (($key === 'OR') || ($key === 'AND')) {
                $groups[$key] = $value;
            } else if (($key === 'EXISTS') || ($key === 'NOT EXISTS')) {
                $existsKeys[$key] = $value;
            } else if (self::isNewSyntax($value)) {
                $newColumns[$key] = $value;
            } else if (self::isPlainEquality((string)$key, $value)) {
                // A bare column key (no operator suffix) carrying a plain scalar value is
                // first-class new syntax - 'age' => 18 means age = 18. It is classified here,
                // BEFORE the $allowLegacy check, so it never counts as "legacy": it fires no
                // deprecation notice and is permitted inside OR/AND groups. A bare key with a
                // null value keeps its documented IS NULL semantics (never 'column = NULL').
                $newColumns[$key] = ($value === null) ? ['IS NULL'] : ['=', $value];
            } else if ($allowLegacy) {
                $legacyColumns[$key] = $value;
            } else {
                throw new Exception(
                    "Error: Legacy shorthand format is not supported inside 'OR'/'AND' groups. Column '" . $key .
                    "' must use the structured ['column' => [OPERATOR, ...values]] format here."
                );
            }
        }

        // The three buckets are always processed in this order - legacy entries, then
        // new-syntax tuples, then OR/AND groups - regardless of the order the caller wrote
        // the keys in. This is deliberate: parameters are registered (and, for PostgreSQL,
        // "$N" placeholder tokens are numbered) in the order the predicates are appended to
        // the set, and PredicateSet::render() emits them in that same order. Processing the
        // buckets in a fixed order keeps the rendered "$N" sequence and the bound-parameter
        // order in lockstep even when legacy and new-syntax entries are mixed in one call.
        if (!empty($legacyColumns)) {
            self::parseLegacy($predicateSet, $legacyColumns, $sql);
        }

        foreach ($newColumns as $column => $tuple) {
            self::parseTuple($predicateSet, (string)$column, $tuple, $sql, $parameterIndex);
        }

        foreach ($existsKeys as $key => $select) {
            if (!($select instanceof AbstractSql)) {
                throw new Exception("Error: The '" . $key . "' key must contain a Sql\Select instance.");
            }
            if ($key === 'EXISTS') {
                $predicateSet->exists($select);
            } else {
                $predicateSet->notExists($select);
            }
        }

        foreach ($groups as $conjunction => $groupList) {
            self::parseGroup($predicateSet, $conjunction, $groupList, $sql, $parameterIndex);
        }

        return $predicateSet;
    }

    /**
     * Parse a reserved 'OR'/'AND' group key into a single combined nested PredicateSet
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $conjunction
     * @param  mixed        $groups
     * @param  AbstractSql  $sql
     * @param  int          $parameterIndex
     * @throws Exception
     * @return void
     */
    protected static function parseGroup(
        PredicateSet $predicateSet, string $conjunction, mixed $groups, AbstractSql $sql, int &$parameterIndex
    ): void
    {
        if (!is_array($groups)) {
            throw new Exception("Error: The '" . $conjunction . "' key must contain an array of condition groups.");
        }

        $combined = new PredicateSet($sql);

        foreach ($groups as $group) {
            if (!is_array($group)) {
                throw new Exception("Error: Each entry under '" . $conjunction . "' must be an array of conditions.");
            }
            if (empty($group)) {
                continue;
            }

            $child = self::parseConditions($group, $sql, false, $parameterIndex);
            $child->setConjunction($conjunction);
            $combined->addPredicateSet($child);
        }

        if ($combined->hasPredicateSets()) {
            $combined->setConjunction('AND');
            $predicateSet->addPredicateSet($combined);
        }
    }

    /**
     * Parse legacy-shaped shorthand entries via the existing Expression parser,
     * firing a deprecation notice and folding the result into the given PredicateSet
     *
     * @param  PredicateSet $predicateSet
     * @param  array        $legacyColumns
     * @param  AbstractSql  $sql
     * @return void
     */
    protected static function parseLegacy(PredicateSet $predicateSet, array $legacyColumns, AbstractSql $sql): void
    {
        $columnList = implode(', ', array_map('strval', array_keys($legacyColumns)));

        trigger_error(
            "Deprecated: The shorthand column format used for [" . $columnList . "] is deprecated and will be " .
            "removed in pop-db v8. Use the structured format instead, e.g. ['column' => ['>=', value]]. " .
            "See README.md for the new syntax.",
            E_USER_DEPRECATED
        );

        $result = Expression::parseShorthand($legacyColumns, $sql->getPlaceholder());

        $predicateSet->addExpressions($result['expressions']);
        $predicateSet->addParameters($result['params']);

        if ($sql->getPlaceholder() === '$') {
            foreach ($result['params'] as $ignored) {
                $sql->incrementParameterCount();
            }
        }
    }

    /**
     * Determine if a shorthand column value uses the new structured (operator-tuple) syntax
     *
     * @param  mixed $value
     * @return bool
     */
    public static function isNewSyntax(mixed $value): bool
    {
        return (is_array($value) && isset($value[0]) && is_string($value[0]) &&
            array_key_exists(strtoupper($value[0]), self::OPERATORS));
    }

    /**
     * Determine if a shorthand entry is plain equality, i.e. a bare column key carrying no
     * operator suffix paired with a scalar (or null) value
     *
     * A '(value1, value2)'-shaped string value is excluded: that is the legacy packed
     * BETWEEN shape, which must keep routing through Expression::parseShorthand(). Its
     * documented replacement is the unambiguous ['column' => ['BETWEEN', v1, v2]] tuple.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return bool
     */
    public static function isPlainEquality(string $key, mixed $value): bool
    {
        if (($value !== null) && !is_scalar($value)) {
            return false;
        }
        if (is_string($value) && str_starts_with($value, '(') && str_ends_with($value, ')')) {
            return false;
        }

        ['column' => $column, 'operator' => $operator] = Operator::parse($key);

        return (($column === $key) && ($operator === '='));
    }

    /**
     * Parse a single new-syntax operator tuple onto the given PredicateSet
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $column
     * @param  array        $tuple
     * @param  AbstractSql  $sql
     * @param  int          $parameterIndex
     * @throws Exception
     * @return void
     */
    protected static function parseTuple(
        PredicateSet $predicateSet, string $column, array $tuple, AbstractSql $sql, int &$parameterIndex
    ): void
    {
        $operator = strtoupper(array_shift($tuple));
        $spec     = self::OPERATORS[$operator];
        $method   = $spec['method'];

        $jsonPath = null;
        if (str_contains($column, '->')) {
            [$column, $jsonPath] = explode('->', $column, 2);
        }

        if ($jsonPath !== null) {
            $jsonMethods = ['=' => 'jsonEqualTo', '!=' => 'jsonNotEqualTo', 'CONTAINS' => 'jsonContains'];
            if (!isset($jsonMethods[$operator])) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator is not supported for JSON path access (column '" .
                    $column . "->" . $jsonPath . "')."
                );
            }
            if (count($tuple) !== 1) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "->" . $jsonPath .
                    "' requires exactly 1 value, " . count($tuple) . ' given.'
                );
            }

            $jsonMethod = $jsonMethods[$operator];
            if ($operator === 'CONTAINS') {
                $predicateSet->{$jsonMethod}($column, $jsonPath, $tuple[0]);
            } else {
                $placeholder = self::addParameter($predicateSet, $sql, $column, $tuple[0], $parameterIndex);
                $predicateSet->{$jsonMethod}($column, $jsonPath, $placeholder);
            }
            return;
        }

        if ($operator === 'CONTAINS') {
            throw new Exception(
                "Error: The 'CONTAINS' operator requires a JSON path in the column key, e.g. 'column->\$.path'."
            );
        }

        if (!empty($spec['multi'])) {
            if (isset($tuple[0]) && ($tuple[0] instanceof AbstractSql)) {
                self::callMulti($predicateSet, $method, $column, $tuple[0]);
            } else {
                if (!isset($tuple[0]) || !is_array($tuple[0])) {
                    throw new Exception(
                        "Error: The '" . $operator . "' operator for column '" . $column .
                        "' requires an array of values or a Sql\Select instance."
                    );
                }
                if (empty($tuple[0])) {
                    throw new Exception(
                        "Error: The '" . $operator . "' operator for column '" . $column .
                        "' requires at least 1 value, 0 given."
                    );
                }

                $placeholders = [];
                foreach ($tuple[0] as $val) {
                    $placeholders[] = self::addParameter($predicateSet, $sql, $column, $val, $parameterIndex);
                }
                self::callMulti($predicateSet, $method, $column, $placeholders);
            }
        } else if ($spec['arity'] === 0) {
            if (count($tuple) !== 0) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' does not accept any values."
                );
            }
            self::callArity0($predicateSet, $method, $column);
        } else if ($spec['arity'] === 1) {
            if (count($tuple) !== 1) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' requires exactly 1 value, " .
                    count($tuple) . ' given.'
                );
            }
            if ($tuple[0] instanceof AbstractSql) {
                if (!in_array($operator, ['=', '!=', '>', '>=', '<', '<='], true)) {
                    throw new Exception(
                        "Error: A Sql\Select instance is not a supported value for the '" . $operator . "' operator."
                    );
                }
                self::callArity1Mixed($predicateSet, $method, $column, $tuple[0]);
            } else {
                $placeholder = self::addParameter($predicateSet, $sql, $column, $tuple[0], $parameterIndex);
                self::callArity1($predicateSet, $method, $column, $placeholder);
            }
        } else {
            if (count($tuple) !== 2) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' requires exactly 2 values, " .
                    count($tuple) . ' given.'
                );
            }
            $placeholder1 = self::addParameter($predicateSet, $sql, $column, $tuple[0], $parameterIndex);
            $placeholder2 = self::addParameter($predicateSet, $sql, $column, $tuple[1], $parameterIndex);
            self::callArity2($predicateSet, $method, $column, $placeholder1, $placeholder2);
        }
    }

    /**
     * Dispatch a 2-value ('in'/'notIn') PredicateSet call
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $method
     * @param  string       $column
     * @param  mixed        $values
     * @return void
     */
    protected static function callMulti(PredicateSet $predicateSet, string $method, string $column, mixed $values): void
    {
        match ($method) {
            'in'    => $predicateSet->in($column, $values),
            'notIn' => $predicateSet->notIn($column, $values),
            default => throw new Exception("Error: Unsupported multi-value method '" . $method . "'."),
        };
    }

    /**
     * Dispatch a no-value ('isNull'/'isNotNull') PredicateSet call
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $method
     * @param  string       $column
     * @return void
     */
    protected static function callArity0(PredicateSet $predicateSet, string $method, string $column): void
    {
        match ($method) {
            'isNull'    => $predicateSet->isNull($column),
            'isNotNull' => $predicateSet->isNotNull($column),
            default     => throw new Exception("Error: Unsupported no-value method '" . $method . "'."),
        };
    }

    /**
     * Dispatch a single-value PredicateSet call whose value is a bound placeholder string
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $method
     * @param  string       $column
     * @param  string       $value
     * @return void
     */
    protected static function callArity1(PredicateSet $predicateSet, string $method, string $column, string $value): void
    {
        match ($method) {
            'equalTo'              => $predicateSet->equalTo($column, $value),
            'notEqualTo'           => $predicateSet->notEqualTo($column, $value),
            'greaterThan'          => $predicateSet->greaterThan($column, $value),
            'greaterThanOrEqualTo' => $predicateSet->greaterThanOrEqualTo($column, $value),
            'lessThan'             => $predicateSet->lessThan($column, $value),
            'lessThanOrEqualTo'    => $predicateSet->lessThanOrEqualTo($column, $value),
            'like'                 => $predicateSet->like($column, $value),
            'notLike'              => $predicateSet->notLike($column, $value),
            default                => throw new Exception("Error: Unsupported single-value method '" . $method . "'."),
        };
    }

    /**
     * Dispatch a single-value PredicateSet call whose value is a nested Sql\Select instance
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $method
     * @param  string       $column
     * @param  AbstractSql  $value
     * @return void
     */
    protected static function callArity1Mixed(PredicateSet $predicateSet, string $method, string $column, AbstractSql $value): void
    {
        match ($method) {
            'equalTo'              => $predicateSet->equalTo($column, $value),
            'notEqualTo'           => $predicateSet->notEqualTo($column, $value),
            'greaterThan'          => $predicateSet->greaterThan($column, $value),
            'greaterThanOrEqualTo' => $predicateSet->greaterThanOrEqualTo($column, $value),
            'lessThan'             => $predicateSet->lessThan($column, $value),
            'lessThanOrEqualTo'    => $predicateSet->lessThanOrEqualTo($column, $value),
            default                => throw new Exception("Error: Unsupported single-value method '" . $method . "'."),
        };
    }

    /**
     * Dispatch a two-value ('between'/'notBetween') PredicateSet call
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $method
     * @param  string       $column
     * @param  string       $value1
     * @param  string       $value2
     * @return void
     */
    protected static function callArity2(
        PredicateSet $predicateSet, string $method, string $column, string $value1, string $value2
    ): void
    {
        match ($method) {
            'between'    => $predicateSet->between($column, $value1, $value2),
            'notBetween' => $predicateSet->notBetween($column, $value1, $value2),
            default      => throw new Exception("Error: Unsupported two-value method '" . $method . "'."),
        };
    }

    /**
     * Register a bound parameter under a tree-unique key and return the dialect-correct
     * placeholder token that refers to it
     *
     * For ':'-style dialects (SQLite/PDO) the returned token is ':' . the parameter key, so
     * the token in the rendered SQL and the key in the parameter array always match exactly.
     * For '?'-style (MySQL/SQL Server) and '$'-style (PostgreSQL) dialects only the parameter
     * ORDER matters at bind time, but the key must still be unique so that the array_merge()
     * in PredicateSet::getParameters() cannot drop it.
     *
     * @param  PredicateSet $predicateSet
     * @param  AbstractSql  $sql
     * @param  string       $column
     * @param  mixed        $value
     * @param  int          $parameterIndex
     * @return string
     */
    protected static function addParameter(
        PredicateSet $predicateSet, AbstractSql $sql, string $column, mixed $value, int &$parameterIndex
    ): string
    {
        $parameterIndex++;

        $key = self::createParameterKey($column, $parameterIndex);
        $predicateSet->addParameter($key, $value);

        $placeholder = $sql->getPlaceholder();

        if ($placeholder === ':') {
            return ':' . $key;
        } else if ($placeholder === '$') {
            $sql->incrementParameterCount();
            return '$' . $sql->getParameterCount();
        } else {
            return '?';
        }
    }

    /**
     * Create a tree-unique, bind-safe parameter key for a column
     *
     * The counter comes FIRST, so every generated key starts with a digit. That is what keeps
     * these keys from ever colliding with the keys the legacy path emits: the (unmodified)
     * Expression::parseShorthand() derives its keys straight from the column name, as either
     * '<column>' or '<column><digit>', and a column name never starts with a digit. Putting
     * the counter last would allow a column literally named 'line_1' to collide with the
     * first generated key for a column named 'line'.
     *
     * Any character that is not valid in a named placeholder (e.g. the '.' of a
     * table-qualified column) is normalized to an underscore.
     *
     * @param  string $column
     * @param  int    $parameterIndex
     * @return string
     */
    protected static function createParameterKey(string $column, int $parameterIndex): string
    {
        return $parameterIndex . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
    }

}
