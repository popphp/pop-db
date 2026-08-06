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
namespace Pop\Db\Sql\Parser;

use Pop\Db\Sql\AbstractSql;
use Pop\Db\Sql\PredicateSet;

/**
 * Structured shorthand condition parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
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
        'NOT LIKE'    => ['method' => 'notLike',               'arity' => 1],
        'IN'          => ['method' => 'in',                    'arity' => 1, 'multi' => true],
        'NOT IN'      => ['method' => 'notIn',                 'arity' => 1, 'multi' => true],
        'BETWEEN'     => ['method' => 'between',               'arity' => 2],
        'NOT BETWEEN' => ['method' => 'notBetween',            'arity' => 2],
        'IS NULL'     => ['method' => 'isNull',                 'arity' => 0],
        'IS NOT NULL' => ['method' => 'isNotNull',              'arity' => 0],
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
        $predicateSet  = new PredicateSet($sql);
        $legacyColumns = [];
        $newColumns    = [];

        foreach ($columns as $key => $value) {
            if (self::isNewSyntax($value)) {
                $newColumns[$key] = $value;
            } else if ($allowLegacy) {
                $legacyColumns[$key] = $value;
            } else {
                throw new Exception(
                    "Error: Legacy shorthand format is not supported inside 'OR'/'AND' groups. Column '" . $key .
                    "' must use the structured ['column' => [OPERATOR, ...values]] format here."
                );
            }
        }

        foreach ($newColumns as $column => $tuple) {
            self::parseTuple($predicateSet, (string)$column, $tuple, $sql);
        }

        // Handle legacy/scalar columns as equals (but skip arrays pending Task 8)
        foreach ($legacyColumns as $column => $value) {
            if (!is_array($value)) {
                $placeholder = self::nextPlaceholder($sql, $column);
                $predicateSet->addParameter($column, $value);
                $predicateSet->equalTo($column, $placeholder);
            }
        }

        return $predicateSet;
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
     * Parse a single new-syntax operator tuple onto the given PredicateSet
     *
     * @param  PredicateSet $predicateSet
     * @param  string       $column
     * @param  array        $tuple
     * @param  AbstractSql  $sql
     * @throws Exception
     * @return void
     */
    protected static function parseTuple(PredicateSet $predicateSet, string $column, array $tuple, AbstractSql $sql): void
    {
        $operator = strtoupper(array_shift($tuple));
        $spec     = self::OPERATORS[$operator];
        $method   = $spec['method'];

        if (!empty($spec['multi'])) {
            if (!isset($tuple[0]) || !is_array($tuple[0])) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' requires an array of values."
                );
            }

            $placeholders = [];
            foreach ($tuple[0] as $i => $val) {
                $placeholders[] = self::nextPlaceholder($sql, $column, $i + 1);
                $predicateSet->addParameter($column . '_' . ($i + 1), $val);
            }
            $predicateSet->{$method}($column, $placeholders);
        } else if ($spec['arity'] === 0) {
            if (count($tuple) !== 0) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' does not accept any values."
                );
            }
            $predicateSet->{$method}($column);
        } else if ($spec['arity'] === 1) {
            if (count($tuple) !== 1) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' requires exactly 1 value, " .
                    count($tuple) . ' given.'
                );
            }
            $placeholder = self::nextPlaceholder($sql, $column);
            $predicateSet->addParameter($column, $tuple[0]);
            $predicateSet->{$method}($column, $placeholder);
        } else {
            if (count($tuple) !== 2) {
                throw new Exception(
                    "Error: The '" . $operator . "' operator for column '" . $column . "' requires exactly 2 values, " .
                    count($tuple) . ' given.'
                );
            }
            $placeholder1 = self::nextPlaceholder($sql, $column, 1);
            $placeholder2 = self::nextPlaceholder($sql, $column, 2);
            $predicateSet->addParameter($column . '_1', $tuple[0]);
            $predicateSet->addParameter($column . '_2', $tuple[1]);
            $predicateSet->{$method}($column, $placeholder1, $placeholder2);
        }
    }

    /**
     * Generate the next dialect-correct placeholder token
     *
     * @param  AbstractSql $sql
     * @param  string      $column
     * @param  ?int        $index
     * @return string
     */
    protected static function nextPlaceholder(AbstractSql $sql, string $column, ?int $index = null): string
    {
        $placeholder = $sql->getPlaceholder();

        if ($placeholder === ':') {
            return ':' . $column . (($index !== null) ? $index : '');
        } else if ($placeholder === '$') {
            $sql->incrementParameterCount();
            return '$' . $sql->getParameterCount();
        } else {
            return '?';
        }
    }

}
