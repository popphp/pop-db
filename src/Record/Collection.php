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
namespace Pop\Db\Record;
use Pop\Utils;
/**
 * Record collection class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.3.0
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
     * @param  array    $options
     * @return array
     */
    public function toArray($options = []): array
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

        if (!empty($options)) {
            if (array_key_exists('column', $options) && !empty($options['column'])) {
                // return simple array of one column
                $items = array_column($items, $options['column']);
            } else if (array_key_exists('key', $options)) {
                if (array_key_exists('isUnique', $options) && $options['isUnique'] == true) {
                    // return associative array sorted by unique column
                    $items = array_reduce($items, function($accumulator, $item) use ($options) {
                        $accumulator[$item[$options['key']]] = $item;
                        return $accumulator;
                    });
                } else {
                    // return associative array of arrays sorted by non-unique column
                    $items = array_reduce($items, function($accumulator, $item) use ($options, $items) {
                        $accumulator[$item[$options['key']]][] = $item;
                        return $accumulator;
                    });
                }
            }
        }

        if ($items === null) {
            $items = [];
        }

        return $items;
    }

}