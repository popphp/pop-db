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
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractClause;
use Pop\Db\Sql\AbstractSql;

/**
 * Abstract predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
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
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @return string
     */
    abstract public function render(AbstractSql $sql): string;

}
