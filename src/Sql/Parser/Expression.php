<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Parser;

/**
 * Predicate expression parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Expression
{

    /**
     * Allowed operators
     * @var array
     */
    protected static $operators = [
        '>=', '<=', '!=', '=', '>', '<',
        'NOT LIKE', 'LIKE', 'NOT BETWEEN', 'BETWEEN',
        'NOT IN', 'IN', 'IS NOT NULL', 'IS NULL'
    ];

    /**
     * Method to parse a string expression into its components
     *
     * @param  string $expression
     * @return string
     */
    public static function parse($expression)
    {
        $column   = null;
        $operator = null;
        $value    = null;

        if (stripos($expression, 'NULL') !== false) {

        } else if (stripos($expression, 'IN') !== false) {

        } else if (stripos($expression, 'BETWEEN') !== false) {

        } else if (stripos($expression, 'LIKE') !== false) {

        } else {
            [$column, $operator, $value] = explode(' ', $expression);
        }

    }

}