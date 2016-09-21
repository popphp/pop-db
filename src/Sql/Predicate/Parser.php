<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Predicate;

/**
 * Predicate parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Parser
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
     * Parse a predicate string
     *
     * @param  string $predicate
     * @return array
     */
    public static function parse($predicate)
    {
        $predicates = [];

        foreach (self::$operators as $op) {
            // If operator IS NULL or IS NOT NULL
            if ((strpos($op, 'NULL') !== false) && (strpos($predicate, $op) !== false)) {
                $combine = (substr($predicate, -3) == ' OR') ? 'OR' : 'AND';
                $value   = null;
                $column  = trim(substr($predicate, 0, strpos($predicate, ' ')));
                // Remove any quotes from the column
                if (((substr($column, 0, 1) == '"') && (substr($column, -1) == '"')) ||
                    ((substr($column, 0, 1) == "'") && (substr($column, -1) == "'")) ||
                    ((substr($column, 0, 1) == '`') && (substr($column, -1) == '`'))) {
                    $column = substr($column, 1);
                    $column = substr($column, 0, -1);
                }

                $predicates = [$column, $op, $value, $combine];
            } else if ((strpos($predicate, ' ' . $op . ' ') !== false) && ((strpos($predicate, ' NOT ' . $op . ' ') === false))) {
                $ary    = explode($op, $predicate);
                $column = trim($ary[0]);
                $value  = trim($ary[1]);

                // Remove any quotes from the column
                if (((substr($column, 0, 1) == '"') && (substr($column, -1) == '"')) ||
                    ((substr($column, 0, 1) == "'") && (substr($column, -1) == "'")) ||
                    ((substr($column, 0, 1) == '`') && (substr($column, -1) == '`'))) {
                    $column = substr($column, 1);
                    $column = substr($column, 0, -1);
                }

                // Remove any quotes from the value
                if (((substr($value, 0, 1) == '"') && (substr($value, -1) == '"')) ||
                    ((substr($value, 0, 1) == "'") && (substr($value, -1) == "'")) ||
                    ((substr($column, 0, 1) == '`') && (substr($column, -1) == '`'))) {
                    $value = substr($value, 1);
                    $value = substr($value, 0, -1);
                    // Else, create array of values if the value is a comma-separated list
                } else if ((substr($value, 0, 1) == '(') && (substr($value, -1) == ')') && (strpos($value, ',') !== false)) {
                    $value = substr($value, 1);
                    $value = substr($value, 0, -1);
                    $value = str_replace(', ', ',', $value);
                    $value = explode(',', $value);
                }

                if (!is_array($value) && (substr($value, -3) == ' OR')) {
                    $value   = substr($value, 0, -3);
                    $combine = 'OR';
                } else {
                    $combine = 'AND';
                }

                if (is_numeric($value)) {
                    if (strpos($value, '.') !== false) {
                        $value = (float)$value;
                    } else {
                        $value = (int)$value;
                    }
                } else if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_numeric($v)) {
                            if (strpos($v, '.') !== false) {
                                $value[$k] = (float)$v;
                            } else {
                                $value[$k] = (int)$v;
                            }
                        }
                    }
                }
                $predicates = [$column, $op, $value, $combine];
            }

        }

        return $predicates;
    }

}