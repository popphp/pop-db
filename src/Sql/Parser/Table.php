<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Parser;

/**
 * Table parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Table
{

    /**
     * Method to convert a camelCase string to an under_score string for database table naming conventions
     *
     * @param  string $tableClass
     * @return string
     */
    public static function parse(string $tableClass): string
    {
        $chars   = str_split($tableClass);
        $dbTable = null;

        foreach ($chars as $i => $char) {
            if ($i == 0) {
                $dbTable .= strtolower($char);
            } else {
                $dbTable .= (ctype_upper($char)) ? ('_' . strtolower($char)) : $char;
            }
        }

        return $dbTable;
    }

}