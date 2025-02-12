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
 * Between predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class Between extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the BETWEEN predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(array $values, string $conjunction = 'AND')
    {
        $this->format = '%1 BETWEEN %2 AND %3';
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
