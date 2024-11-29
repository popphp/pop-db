<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractSql;

/**
 * Abstract predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
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
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @return string
     */
    abstract public function render(AbstractSql $sql): string;

}
