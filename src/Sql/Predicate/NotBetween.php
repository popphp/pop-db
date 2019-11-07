<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

use Pop\Db\Sql\AbstractSql;

/**
 * Not Between predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class NotBetween extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the NOT BETWEEN predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     */
    public function __construct(array $values, $conjunction = 'AND')
    {
        $this->format = '%1 NOT BETWEEN %2 AND %3';
        parent::__construct($values, $conjunction);
    }

    /**
     * Render the predicate string
     *
     *
     * @param  AbstractSql $sql
     * @throws Exception
     * @return string
     */
    public function render(AbstractSql $sql)
    {
        if (count($this->values) != 3) {
            throw new Exception('Error: The values array must have 3 values in it.');
        }

        [$column, $value1, $value2] = $this->values;

        $predicate = str_replace(
            ['%1', '%2', '%3'], [$sql->quoteId($column), $sql->quote($value1), $sql->quote($value2)], $this->format
        );

        return '(' . $predicate . ')';
    }

}