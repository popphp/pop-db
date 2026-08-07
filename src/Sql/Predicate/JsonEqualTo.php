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

use Pop\Db\Sql\AbstractSql;

/**
 * Json Equal To predicate class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class JsonEqualTo extends AbstractPredicate
{

    /**
     * Constructor
     *
     * Instantiate the JSON EQUAL TO predicate set object
     *
     * @param  array  $values
     * @param  string $conjunction
     * @throws Exception
     */
    public function __construct(array $values, string $conjunction = 'AND')
    {
        $this->format = '%1 = %2';
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
        if (count($this->values) != 3) {
            throw new Exception('Error: The values array must have 3 values in it (column, path, value).');
        }

        [$column, $path, $value] = $this->values;

        $extract = $sql->jsonExtract($column, $path);

        return '(' . str_replace(
            ['%1', '%2'], [(string)$extract, self::renderJsonValue($sql, $column, $value)], $this->format
        ) . ')';
    }

}
