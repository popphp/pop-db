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
namespace Pop\Db\Sql\Schema\Formatter;

/**
 * Schema formatter abstract class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
 */
abstract class AbstractFormatter
{

    /**
     * Un-quote identifier
     *
     * @param  string $identifier
     * @return string
     */
    public static function unquoteId($identifier)
    {
        if (strpos($identifier, '.') !== false) {
            $identifierAry = explode('.', $identifier);
            foreach ($identifierAry as $key => $val) {
                $first = substr($val, 0, 1);
                if (($first == '`') || ($first == '"') || ($first == '[')) {
                    $val = substr($val, 1);
                    $val = substr($val, 0, -1);
                }
                $identifierAry[$key] = $val;
            }
            $identifier = implode('.', $identifierAry);
        } else {
            $first = substr($identifier, 0, 1);

            if (($first == '`') || ($first == '"') || ($first == '[')) {
                $identifier = substr($identifier, 1);
                $identifier = substr($identifier, 0, -1);
            }
        }

        return $identifier;
    }

}