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

use Pop\Db\Gateway;
use Pop\Db\Sql\Parser;

/**
 * Abstract record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.5.0
 */
abstract class AbstractRecord implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = null;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Row gateway
     * @var Gateway\Row
     */
    protected $rowGateway = null;

    /**
     * Table gateway
     * @var Gateway\Table
     */
    protected $tableGateway = null;

    /**
     * Is new record flag
     * @var boolean
     */
    protected $isNew = false;

    /**
     * Set the table
     *
     * @param  string $table
     * @return AbstractRecord
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table from a class name
     *
     * @param  string $class
     * @return mixed
     */
    public function setTableFromClassName($class = null)
    {
        if (null === $class) {
            $class = get_class($this);
        }

        if (strpos($class, '_') !== false) {
            $cls = substr($class, (strrpos($class, '_') + 1));
        } else if (strpos($class, '\\') !== false) {
            $cls = substr($class, (strrpos($class, '\\') + 1));
        } else {
            $cls = $class;
        }

        return $this->setTable(Parser\Table::parse($cls));
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return AbstractRecord
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return AbstractRecord
     */
    public function setPrimaryKeys(array $keys)
    {
        $this->primaryKeys = $keys;
        return $this;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the full table name (prefix + table)
     *
     * @return string
     */
    public function getFullTable()
    {
        return $this->prefix . $this->table;
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the primary keys
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Get the primary values
     *
     * @return array
     */
    public function getPrimaryValues()
    {
        return (null !== $this->rowGateway) ?
            array_intersect_key($this->rowGateway->getColumns(), array_flip($this->primaryKeys)) : [];
    }

    /**
     * Get the row gateway
     *
     * @return Gateway\Row
     */
    public function getRowGateway()
    {
        return $this->rowGateway;
    }

    /**
     * Get the table gateway
     *
     * @return Gateway\Table
     */
    public function getTableGateway()
    {
        return $this->tableGateway;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->rowGateway->getColumns();
    }

    /**
     * Method to get count of fields in row gateway
     *
     * @return int
     */
    public function count()
    {
        return $this->rowGateway->count();
    }

    /**
     * Method to iterate over the columns
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->rowGateway->getIterator();
    }

    /**
     * Get the rows
     *
     * @return Collection
     */
    public function getRows()
    {
        return new Collection($this->tableGateway->getRows());
    }

    /**
     * Get the rows (alias method)
     *
     * @return Collection
     */
    public function rows()
    {
        return $this->getRows();
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function countRows()
    {
        return $this->tableGateway->getNumberOfRows();
    }

    /**
     * Determine if the result has rows
     *
     * @return boolean
     */
    public function hasRows()
    {
        return ($this->tableGateway->getNumberOfRows() > 0);
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->rowGateway[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->rowGateway[$name])) ? $this->rowGateway[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->rowGateway[$name]);
    }

    /**
     * Magic method to unset $this->rowGateway[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->rowGateway[$name])) {
            unset($this->rowGateway[$name]);
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