<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record;
use Pop\Utils;
/**
 * Record collection class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Collection extends Utils\Collection
{

    /**
     * Method to get collection object items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->data;
    }

    /**
     * Method to get collection object as an array
     *
     * @return array
     */
    public function toArray()
    {
        $items = $this->data;

        foreach ($items as $key => $value) {
            if ($value instanceof AbstractRecord) {
                $items[$key] = $value->toArray();
                if ($value->hasRelationships()) {
                    $relationships = $value->getRelationships();
                    foreach ($relationships as $name => $relationship) {
                        $items[$key][$name] = (is_object($relationship) && method_exists($relationship, 'toArray')) ?
                            $relationship->toArray() : $relationship;
                    }
                }

            }
        }

        return $items;
    }

}