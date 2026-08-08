<?php
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

use Pop\Db\Sql\AbstractSql;

/**
 * Exists predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Exists extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the EXISTS predicate set object
     *
     * @param  mixed  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(mixed $values, string $conjunction = 'AND')
    {
        $this->format = 'EXISTS (%1)';
        parent::__construct($values, $conjunction);
    }

    /**
     * Render the predicate string
     *
     * @param  AbstractSql $sql
     * @throws Exception
     * @return string
     */
    public function render(AbstractSql $sql): string
    {
        if (!($this->values instanceof AbstractSql)) {
            throw new Exception('Error: The value must be a Sql\Select instance.');
        }

        static::assertNoSubqueryAlias($this->values);

        return '(' . str_replace('%1', (string)$this->values, $this->format) . ')';
    }

}
