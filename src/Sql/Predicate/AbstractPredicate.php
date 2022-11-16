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
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractSql;

/**
 * Abstract predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
abstract class AbstractPredicate
{

    /**
     * Format
     * @var string
     */
    protected $format = null;

    /**
     * Values
     * @var mixed
     */
    protected $values = null;

    /**
     * Conjunction
     * @var string
     */
    protected $conjunction = 'AND';

    /**
     * Constructor
     *
     * Instantiate the predicate set object
     *
     * @param  mixed  $values
     * @param  string $conjunction
     */
    public function __construct($values, $conjunction = 'AND')
    {
        $this->setValues($values);
        $this->setConjunction($conjunction);
    }

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set values
     *
     * @param  mixed  $values
     * @return AbstractPredicate
     */
    public function setValues($values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Get the values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Get the conjunction
     *
     * @param  string $conjunction
     * @return AbstractPredicate
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
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @return string
     */
    abstract public function render(AbstractSql $sql);

}