<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Parser;

/**
 * Order parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Order
{

    /**
     * Get the order by values
     *
     * @param  string $orderBy
     * @return array
     */
    public static function parse(string $orderBy): array
    {
        $by    = null;
        $order = null;

        if (stripos($orderBy, 'ASC') !== false) {
            $order = 'ASC';
            $by    = trim(str_replace('ASC', '', $orderBy));
        } else if (stripos($orderBy, 'DESC') !== false) {
            $order = 'DESC';
            $by    = trim(str_replace('DESC', '', $orderBy));
        } else if (stripos($orderBy, 'RAND()') !== false) {
            $order = 'RAND()';
            $by    = trim(str_replace('RAND()', '', $orderBy));
        } else {
            $order = null;
            $by    = $orderBy;
        }

        if (str_contains($by, ',')) {
            $by = array_map('trim', explode(',', $by));
        }

        return ['by' => $by, 'order' => $order];
    }

}
