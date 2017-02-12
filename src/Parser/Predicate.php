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
namespace Pop\Db\Parser;

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
class Predicate
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
                $value      = null;
                $column     = self::stripIdQuotes(trim(substr($predicate, 0, strpos($predicate, ' '))));
                $predicates = [$column, $op, $value];
            } else if ((strpos($predicate, ' ' . $op . ' ') !== false) && ((strpos($predicate, ' NOT ' . $op . ' ') === false))) {
                $ary    = explode($op, $predicate);
                $column = trim($ary[0]);
                $value  = trim($ary[1]);
                $column = self::stripIdQuotes($column);

                // Create array of values if the value is a comma-separated list
                if ((substr($value, 0, 1) == '(') && (substr($value, -1) == ')') && (strpos($value, ',') !== false)) {
                    $value = substr($value, 1);
                    $value = substr($value, 0, -1);
                    $value = str_replace(', ', ',', $value);
                    $value = explode(',', $value);
                // Else, just strip quotes
                } else {
                    $value = self::stripQuotes($value);
                }

                if (is_string($value) && strpos($value, ' AND ')) {
                    $value = explode(' AND ', $value);
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
                $predicates = [$column, $op, $value];
            }

        }

        return $predicates;
    }

    /**
     * Strip ID quotes
     *
     * @param  string $identifier
     * @return string
     */
    public static function stripIdQuotes($identifier)
    {
        if (((substr($identifier, 0, 1) == '"') && (substr($identifier, -1) == '"')) ||
            ((substr($identifier, 0, 1) == '`') && (substr($identifier, -1) == '`')) ||
            ((substr($identifier, 0, 1) == '[') && (substr($identifier, -1) == ']'))) {
            $identifier = substr($identifier, 1);
            $identifier = substr($identifier, 0, -1);
        }

        return $identifier;
    }

    /**
     * Strip quotes
     *
     * @param  string $value
     * @return string
     */
    public static function stripQuotes($value)
    {
        if (((substr($value, 0, 1) == '"') && (substr($value, -1) == '"')) ||
            ((substr($value, 0, 1) == "'") && (substr($value, -1) == "'"))) {
            $value = substr($value, 1);
            $value = substr($value, 0, -1);
        }

        return $value;
    }

}