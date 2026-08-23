<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
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
     * @throws Exception
     */
    public function __construct(array $values, string $conjunction = 'AND')
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
    public function render(AbstractSql $sql): string
    {
        if (count($this->values) != 2) {
            throw new Exception('Error: The values array must have 2 values in it.');
        }

        [$column, $values] = $this->values;

        if ($values instanceof AbstractSql) {
            static::assertNoSubqueryAlias($values);
            $valuesString = (string)$values;
        } else if (is_array($values)) {
            $valuesString = implode(', ', array_map([$sql, 'quote'], $values));
        } else {
            throw new Exception('Error: The 2nd value must be an array of values or a Sql\Select instance.');
        }

        return '(' . str_replace(['%1', '%2'], [$sql->quoteId($column), $valuesString], $this->format) . ')';
    }

}
