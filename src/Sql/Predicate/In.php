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
 * In predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class In extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the IN predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(array $values, string $conjunction = 'AND')
    {
        $this->format = '%1 IN (%2)';
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
    public function render(AbstractSql $sql): string
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
