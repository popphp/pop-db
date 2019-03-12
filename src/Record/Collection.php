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
namespace Pop\Db\Record;

/**
 * Record collection class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Array of items in the collection
     * @var array
     */
    protected $items = null;

    /**
     * Constructor
     *
     * Instantiate the collection object
     *
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getItemsAsArray($items);
    }

    /**
     * Method to get the count of items in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get the first item of the collection
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->items);
    }

    /**
     * Get the next item of the collection
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * Get the current item of the collection
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * Get the last item of the collection
     *
     * @return mixed
     */
    public function last()
    {
        return end($this->items);
    }

    /**
     * Get the key of the current item of the collection
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * Determine if an item exists in the collection
     *
     * @param  mixed   $key
     * @param  boolean $strict
     * @return boolean
     */
    public function contains($key, $strict = false)
    {
        return in_array($key, $this->items, $strict);
    }

    /**
     * Execute a callback over each item
     *
     * @param  callable $callback
     * @return Collection
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Create a new collection from every n-th element
     *
     * @param  int $step
     * @param  int $offset
     * @return Collection
     */
    public function every($step, $offset = 0)
    {
        $new      = [];
        $position = 0;

        foreach ($this->items as $item) {
            if (($position % $step) === $offset) {
                $new[] = $item;
            }
            $position++;
        }

        return new static($new);
    }

    /**
     * Apply filter to the collection
     *
     * @param  callable|null $callback
     * @param  int           $flag
     * @return Collection
     */
    public function filter(callable $callback, $flag = 0)
    {
        return new static(array_filter($this->items, $callback, $flag));
    }

    /**
     * Flip the items in the collection
     *
     * @return Collection
     */
    public function flip()
    {
        foreach ($this->items as $i => $item) {
            $this->items[$i] = array_flip($item);
        }
        return new static($this->items);
    }

    /**
     * Determine if the key exists
     *
     * @param  mixed $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Determine if the collection is empty or not
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get the keys of the collection items
     *
     * @return Collection
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the values of the collection items
     *
     * @return Collection
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Merge the collection with the passed items
     *
     * @param  mixed   $items
     * @param  boolean $recursive
     * @return Collection
     */
    public function merge($items, $recursive = false)
    {
        return ($recursive) ?
            new static(array_merge_recursive($this->items, $this->getItemsAsArray($items))) :
            new static(array_merge($this->items, $this->getItemsAsArray($items)));
    }

    /**
     * Slice the collection for a page
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return Collection
     */
    public function forPage($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * Get and remove the last item from the collection
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed $value
     * @return Collection
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    /**
     * Get and remove the first item from the collection
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Slice the collection
     *
     * @param  int $offset
     * @param  int $length
     * @return Collection
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Splice a portion of the collection
     *
     * @param  int      $offset
     * @param  int|null $length
     * @param  mixed    $replacement
     * @return Collection
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        return ((null === $length) && (count($replacement) == 0)) ?
            new static(array_splice($this->items, $offset)) :
            new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Sort items
     *
     * @param  callable|null $callback
     * @param  int           $flags
     * @return Collection
     */
    public function sort(callable $callback = null, $flags = SORT_REGULAR)
    {
        $items = $this->items;

        if (null !== $callback) {
            uasort($items, $callback);
        } else {
            asort($items, $flags);
        }

        return new static($items);
    }

    /**
     * Sort the collection ascending
     *
     * @param  int $flags
     * @return Collection
     */
    public function sortByAsc($flags = SORT_REGULAR)
    {
        $results = $this->items;
        asort($results, $flags);
        return new static($results);
    }

    /**
     * Sort the collection descending
     *
     * @param  int $flags
     * @return Collection
     */
    public function sortByDesc($flags = SORT_REGULAR)
    {
        $results = $this->items;
        arsort($results, $flags);
        return new static($results);
    }

    /**
     * Method to get collection object items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Method to get collection object as an array
     *
     * @return array
     */
    public function toArray()
    {
        $items = $this->items;
        foreach ($items as $key => $value) {
            if ($value instanceof AbstractRecord) {
                $items[$key] = $value->toArray();
            }
        }
        return $items;
    }

    /**
     * Method to iterate over the collection
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Method to get items as an array
     *
     * @param  mixed $items
     * @return array
     */
    protected function getItemsAsArray($items)
    {
        if ($items instanceof self) {
            $items = $items->getItems();
        } else if ($items instanceof \ArrayObject) {
            $items = (array)$items;
        } else if ($items instanceof \Traversable) {
            $items = iterator_to_array($items);
        }
        return $items;
    }

    /**
     * Magic method to set the property to the value of $this->items[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->items[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->items[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->items[$name])) ? $this->items[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->items[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->items[$name]);
    }

    /**
     * Magic method to unset $this->items[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->items[$name])) {
            unset($this->items[$name]);
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}
