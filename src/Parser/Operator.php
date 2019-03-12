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
namespace Pop\Db\Parser;

/**
 * Operator parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Operator
{

    /**
     * Method to get the operator from the shorthand column name
     *
     * @param string $column
     * @return array
     */
    public static function parse($column)
    {
        $op = '=';

        // LIKE/NOT LIKE shorthand
        if (substr($column, 0, 2) == '-%') {
            $column = substr($column, 2);
            $op     = 'NOT LIKE';
        } else if (substr($column, 0, 1) == '%') {
            $column = substr($column, 1);
            $op     = 'LIKE';
        }
        if (substr($column, -2) == '%-') {
            $column = substr($column, 0, -2);
            $op     = 'NOT LIKE';
        } else if (substr($column, -1) == '%') {
            $column = substr($column, 0, -1);
            $op     = 'LIKE';
        }

        // NOT NULL/IN/BETWEEN shorthand
        if (substr($column, -1) == '-') {
            $column = trim(substr($column, 0, -1));
            $op     = 'NOT';
        }

        // Basic comparison shorthand
        if (substr($column, -2) == '>=') {
            $column = trim(substr($column, 0, -2));
            $op     = '>=';
        } else if (substr($column, -2) == '<=') {
            $column = trim(substr($column, 0, -2));
            $op     = '<=';
        } else if (substr($column, -2) == '!=') {
            $column = trim(substr($column, 0, -2));
            $op     = '!=';
        } else if (substr($column, -1) == '>') {
            $column = trim(substr($column, 0, -1));
            $op     = '>';
        } else if (substr($column, -1) == '<') {
            $column = trim(substr($column, 0, -1));
            $op     = '<';
        }

        return ['column' => $column, 'op' => $op];
    }

}