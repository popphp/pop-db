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
 * Db operator parser class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.1
 */
class Operator
{

    /**
     * Method to get the operator from the column name
     *
     * @param string $column
     * @return array
     */
    public static function parse($column)
    {
        $op = '=';

        if (substr($column, -2) == '>=') {
            $op = '>=';
            $column = trim(substr($column, 0, -2));
        } else if (substr($column, -2) == '<=') {
            $op = '<=';
            $column = trim(substr($column, 0, -2));
        } else if (substr($column, -2) == '!=') {
            $op = '!=';
            $column = trim(substr($column, 0, -2));
        } else if (substr($column, -1) == '>') {
            $op = '>';
            $column = trim(substr($column, 0, -1));
        } else if (substr($column, -1) == '<') {
            $op = '<';
            $column = trim(substr($column, 0, -1));
        }

        return ['column' => $column, 'op' => $op];
    }

}