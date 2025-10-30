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
namespace Pop\Db\Sql\Parser;

/**
 * Operator parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.7.0
 */
class Operator
{

    /**
     * Method to get the operator from the shorthand column name
     *
     * @param  string $column
     * @return array
     */
    public static function parse(string $column): array
    {
        $operator = '=';

        // LIKE/NOT LIKE shorthand
        if (str_starts_with($column, '-%')) {
            $column   = substr($column, 2);
            $operator = 'NOT LIKE';
        } else if (str_starts_with($column, '%')) {
            $column   = substr($column, 1);
            $operator = 'LIKE';
        }
        if (str_ends_with($column, '%-')) {
            $column   = substr($column, 0, -2);
            $operator = 'NOT LIKE';
        } else if (str_ends_with($column, '%')) {
            $column   = substr($column, 0, -1);
            $operator = 'LIKE';
        }

        // NOT NULL/IN/BETWEEN shorthand
        if (str_ends_with($column, '-')) {
            $column   = trim(substr($column, 0, -1));
            $operator = 'NOT';
        }

        // Basic comparison shorthand
        if (str_ends_with($column, '>=')) {
            $column   = trim(substr($column, 0, -2));
            $operator = '>=';
        } else if (str_ends_with($column, '<=')) {
            $column   = trim(substr($column, 0, -2));
            $operator = '<=';
        } else if (str_ends_with($column, '!=')) {
            $column   = trim(substr($column, 0, -2));
            $operator = '!=';
        } else if (str_ends_with($column, '>')) {
            $column   = trim(substr($column, 0, -1));
            $operator = '>';
        } else if (str_ends_with($column, '<')) {
            $column   = trim(substr($column, 0, -1));
            $operator = '<';
        }

        return ['column' => $column, 'operator' => $operator];
    }

}
