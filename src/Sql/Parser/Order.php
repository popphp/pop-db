<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
class Order
{

    /**
     * Get the order by values
     *
     * A string carrying no direction falls back to ASC, which is SQL's own default for a bare
     * column. A leading '-' is the shorthand for descending, the same convention that
     * Model\AbstractDataModel::getOrderBy() applies to its sort parameter.
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
        } else if (str_starts_with(trim($orderBy), '-')) {
            $order = 'DESC';
            $by    = trim(substr(trim($orderBy), 1));
        } else {
            $order = 'ASC';
            $by    = trim($orderBy);
        }

        if (str_contains($by, ',')) {
            $by = array_map('trim', explode(',', $by));
        }

        return ['by' => $by, 'order' => $order];
    }

}
