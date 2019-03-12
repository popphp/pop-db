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
 * Order parser class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Order
{

    /**
     * Get the order by values
     *
     * @param  string $order
     * @return array
     */
    public static function parse($order)
    {
        $by  = null;
        $ord = null;

        if (stripos($order, 'ASC') !== false) {
            $by  = trim(str_replace('ASC', '', $order));
            $ord = 'ASC';
        } else if (stripos($order, 'DESC') !== false) {
            $by  = trim(str_replace('DESC', '', $order));
            $ord = 'DESC';
        } else if (stripos($order, 'RAND()') !== false) {
            $by  = trim(str_replace('RAND()', '', $order));
            $ord = 'RAND()';
        } else {
            $by  = $order;
            $ord = null;
        }

        if (strpos($by, ',') !== false) {
            $by = str_replace(', ', ',', $by);
            $by = explode(',', $by);
        }

        return ['by' => $by, 'order' => $ord];
    }

}