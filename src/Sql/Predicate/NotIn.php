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
 * Not In predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class NotIn extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the NOT IN predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     */
    public function __construct(array $values, $conjunction = 'AND')
    {
        $this->format = '%1 NOT IN (%2)';
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
        if (count($this->values) != 2) {
            throw new Exception('Error: The values array must have 2 values in it.');
        }
        if (!is_array($this->values[1])) {
            throw new Exception('Error: The 2nd value must be an array of values.');
        }

        [$column, $values] = $this->values;

        $values = array_map([$sql, 'quote'], $values);

        return '(' . str_replace(['%1', '%2'], [$sql->quoteId($column), implode(', ', $values)], $this->format) . ')';
    }

}