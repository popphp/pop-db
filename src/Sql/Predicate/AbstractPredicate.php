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
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractClause;
use Pop\Db\Sql\AbstractSql;

/**
 * Abstract predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
abstract class AbstractPredicate
{

    /**
     * Format
     * @var ?string
     */
    protected ?string $format = null;

    /**
     * Values
     * @var mixed
     */
    protected mixed $values = null;

    /**
     * Conjunction
     * @var string
     */
    protected string $conjunction = 'AND';

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  mixed  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(mixed $values, string $conjunction = 'AND')
    {
        $this->setValues($values);
        $this->setConjunction($conjunction);
    }

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Set values
     *
     * @param  mixed  $values
     * @return AbstractPredicate
     */
    public function setValues(mixed $values): AbstractPredicate
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Get the values
     *
     * @return mixed
     */
    public function getValues(): mixed
    {
        return $this->values;
    }

    /**
     * Get the conjunction
     *
     * @param  string $conjunction
     * @throws Exception
     * @return AbstractPredicate
     */
    public function setConjunction(string $conjunction): AbstractPredicate
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
    public function getConjunction(): string
    {
        return $this->conjunction;
    }

    /**
     * Assert that a nested Sql instance used as a subquery value has no alias set.
     *
     * An aliased Select renders itself as "(SELECT ...) AS `alias`", which is only valid
     * in a FROM/JOIN context. Embedded inside a predicate it would produce silently
     * invalid SQL, so reject it up front with a clear error.
     *
     * @param  AbstractSql $value
     * @throws Exception
     * @return void
     */
    protected static function assertNoSubqueryAlias(AbstractSql $value): void
    {
        if (($value instanceof AbstractClause) && ($value->getAlias() !== null)) {
            throw new Exception(
                'Error: A Select instance used as a subquery value cannot have an alias set ' .
                '(an alias is only valid for FROM/JOIN subqueries).'
            );
        }
    }

    /**
     * Render a single predicate value: a nested Select/Sql instance embeds as a
     * parenthesized subquery, anything else quotes as a literal/placeholder as before
     *
     * @param  AbstractSql $sql
     * @param  mixed       $value
     * @throws Exception
     * @return string
     */
    protected static function renderValue(AbstractSql $sql, mixed $value): string
    {
        if ($value instanceof AbstractSql) {
            static::assertNoSubqueryAlias($value);
            return '(' . $value . ')';
        }

        return $sql->quote($value);
    }

    /**
     * Render a JSON path comparison value
     *
     * PostgreSQL's JSON extraction operators ('->>' / '#>>') always return text, and PostgreSQL
     * has no implicit text-to-number comparison: a bare numeric literal on the right-hand side
     * fails at execution time with "operator does not exist: text = integer". So on PostgreSQL a
     * plain scalar value is forced to a quoted text literal, which compares correctly against the
     * extracted text. Two cases must NOT be force-quoted and are handed to renderValue()
     * unchanged: a nested Sql instance (which embeds as a parenthesized subquery), and a bound
     * parameter placeholder token (which the database binds as text anyway).
     *
     * @param  AbstractSql $sql
     * @param  ?string     $column
     * @param  mixed       $value
     * @throws Exception
     * @return string
     */
    protected static function renderJsonValue(AbstractSql $sql, ?string $column, mixed $value): string
    {
        if (($value instanceof AbstractSql) || !$sql->isPgsql() || $sql->isParameter($value, $column)) {
            return static::renderValue($sql, $value);
        }

        $quoted = (string)$sql->quote((string)$value, true);

        // quote()'s forced branch still leaves an all-digit string unquoted, which is exactly the
        // case that breaks on PostgreSQL, so wrap it. Digits need no escaping.
        if (!str_starts_with($quoted, "'")) {
            $quoted = "'" . $quoted . "'";
        }

        return $quoted;
    }

    /**
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @return string
     */
    abstract public function render(AbstractSql $sql): string;

}
