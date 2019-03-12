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
 * Table parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Table
{

    /**
     * Method to convert a camelCase string to an under_score string for database table naming conventions
     *
     * @param  string $string
     * @return string
     */
    public static function parse($string)
    {
        $strAry  = str_split($string);
        $convert = null;
        $i = 0;

        foreach ($strAry as $chr) {
            if ($i == 0) {
                $convert .= strtolower($chr);
            } else {
                $convert .= (ctype_upper($chr)) ? ('_' . strtolower($chr)) : $chr;
            }
            $i++;
        }

        return $convert;
    }

}